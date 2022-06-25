<?php

namespace BlitzPHP\Debug;

use BlitzPHP\Core\Application;
use BlitzPHP\Debug\Toolbar\Collectors\BaseCollector;
use BlitzPHP\Debug\Toolbar\Collectors\Config;
use BlitzPHP\Debug\Toolbar\Collectors\HistoryCollector;
use BlitzPHP\Formatter\JsonFormatter;
use BlitzPHP\Formatter\XmlFormatter;
use BlitzPHP\Http\Response;
use BlitzPHP\Http\ServerRequest;
use BlitzPHP\Loader\Services;
use BlitzPHP\View\Parser;
use GuzzleHttp\Psr7\Utils;
use Kint\Kint;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;

/**
 * Affiche une barre d'outils avec des bits de statistiques pour aider un développeur dans le débogage.
 *
 * Inspiration: http://prophiler.fabfuel.de
 * 
 * @credit	<a href="https://codeigniter.com">CodeIgniter 4.2 - CodeIgniter\Debug\Toolbar</a>
 */
class Toolbar
{
    /**
     * Paramètres de configuration de la barre d'outils.
     *
     * @var stdClass
     */
    protected $config;

    /**
     * Collecteurs à utiliser et à exposer.
     *
     * @var BaseCollector[]
     */
    protected $collectors = [];

	/**
	 * Dossier de sauvegarde des information de debogage
	 *
	 * @var string
	 */
	private $debugPath = STORAGE_PATH . 'debugbar';


	/**
	 * Constructeur
	 */
    public function __construct(?stdClass $config = null)
    {
        $this->config = $config ?? (object) config('toolbar');

        foreach ($this->config->collectors as $collector) {
            if (! class_exists($collector)) {
				logger()->critical(
                    'Toolbar collector does not exist (' . $collector . ').'
                    . ' Please check $collectors in the app/Config/toolbar.php file.'
                );

                continue;
            }

            $this->collectors[] = new $collector();
        }
    }

    /**
     * Renvoie toutes les données requises par la barre de débogage
     *
     * @param float           $startTime Heure de début de l'application
     *
     * @return string Données encodées en JSON
     */
    public function run(float $startTime, float $totalTime, ServerRequest $request, Response $response): string
    {
        // Éléments de données utilisés dans la vue.
        $data['url']             = current_url();
        $data['method']          = strtoupper($request->getMethod());
        $data['isAJAX']          = $request->isAJAX();
        $data['startTime']       = $startTime;
        $data['totalTime']       = $totalTime * 1000;
        $data['totalMemory']     = number_format((memory_get_peak_usage()) / 1024 / 1024, 3);
        $data['segmentDuration'] = $this->roundTo($data['totalTime'] / 7);
        $data['segmentCount']    = (int) ceil($data['totalTime'] / $data['segmentDuration']);
        $data['blitzVersion']    = Application::VERSION;
        $data['collectors']      = [];

        foreach ($this->collectors as $collector) {
            $data['collectors'][] = $collector->getAsArray();
        }

        foreach ($this->collectVarData() as $heading => $items) {
            $varData = [];

            if (is_array($items)) {
                foreach ($items as $key => $value) {
                    if (is_string($value)) {
                        $varData[esc($key)] = esc($value);
                    } else {
                        $oldKintMode       = Kint::$mode_default;
                        $oldKintCalledFrom = Kint::$display_called_from;

                        Kint::$mode_default        = Kint::MODE_RICH;
                        Kint::$display_called_from = false;

                        $kint = @Kint::dump($value);
                        $kint = substr($kint, strpos($kint, '</style>') + 8);

                        Kint::$mode_default        = $oldKintMode;
                        Kint::$display_called_from = $oldKintCalledFrom;

                        $varData[esc($key)] = $kint;
                    }
                }
            }

            $data['vars']['varData'][esc($heading)] = $varData;
        }

        if (! empty($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                // Remplacez les données binaires par une chaîne pour éviter l'échec de json_encode.
                if (is_string($value) && preg_match('~[^\x20-\x7E\t\r\n]~', $value)) {
                    $value = 'binary data';
                }

                $data['vars']['session'][esc($key)] = is_string($value) ? esc($value) : '<pre>' . esc(print_r($value, true)) . '</pre>';
            }
        }


		foreach ($request->getQueryParams() as $name => $value) {
			$data['vars']['get'][esc($name)] = is_array($value) ? '<pre>' . esc(print_r($value, true)) . '</pre>' : esc($value);
		}

		foreach ($request->getParsedBody() As $name => $value) {
			$data['vars']['post'][esc($name)] = is_array($value) ? '<pre>' . esc(print_r($value, true)) . '</pre>' : esc($value);
		}

		foreach ($request->getHeaders() As $header => $value) {
			if (empty($value)) {
				continue;
			}

			if (! is_array($value)) {
				$value = [$value];
			}

			foreach ($value As $h) {
				if (is_object($h) AND method_exists($h, 'getName') AND method_exists($h, 'getValueLine')) {
					$data['vars']['headers'][esc($h->getName())] = esc($h->getValueLine());
				}
			}
		}

		foreach ($request->getCookieParams() As $name => $value) {
			$data['vars']['cookies'][esc($name)] = esc($value);
		}

		$data['vars']['request'] = ($request->is('ssl') ? 'HTTPS' : 'HTTP') . '/' . $request->getProtocolVersion();

        $data['vars']['response'] = [
            'statusCode'  => $response->getStatusCode(),
            'reason'      => esc($response->getReasonPhrase()),
            'contentType' => esc($response->getHeaderLine('content-type')),
            'headers'     => [],
        ];

		foreach ($response->getHeaders() As $header => $value) {
			if (empty($value)) {
				continue;
			}

			if (! is_array($value)) {
				$value = [$value];
			}

			foreach ($value As $h) {
				if (is_object($h) AND method_exists($h, 'getName') AND method_exists($h, 'getValueLine')) {
					$data['vars']['response']['headers'][esc($h->getName())] = esc($h->getValueLine());
				}
			}
		}

        $data['config'] = Config::display();


        return json_encode($data);
    }

    /**
     * Appelé dans la vue pour afficher la chronologie elle-même.
     */
    protected function renderTimeline(array $collectors, float $startTime, int $segmentCount, int $segmentDuration, array &$styles): string
    {
        $rows       = $this->collectTimelineData($collectors);
        $styleCount = 0;

        // Utiliser la fonction de rendu récursif
        return $this->renderTimelineRecursive($rows, $startTime, $segmentCount, $segmentDuration, $styles, $styleCount);
    }

    /**
     * Restitue de manière récursive les éléments de la chronologie et leurs enfants.
     */
    protected function renderTimelineRecursive(array $rows, float $startTime, int $segmentCount, int $segmentDuration, array &$styles, int &$styleCount, int $level = 0, bool $isChild = false): string
    {
        $displayTime = $segmentCount * $segmentDuration;

        $output = '';

        foreach ($rows as $row) {
            $hasChildren = isset($row['children']) && ! empty($row['children']);
            $isQuery     = isset($row['query']) && ! empty($row['query']);

            // Ouvrir la chronologie du contrôleur par défaut
            $open = $row['name'] === 'Controller';

            if ($hasChildren || $isQuery) {
                $output .= '<tr class="timeline-parent' . ($open ? ' timeline-parent-open' : '') . '" id="timeline-' . $styleCount . '_parent" onclick="ciDebugBar.toggleChildRows(\'timeline-' . $styleCount . '\');">';
            } else {
                $output .= '<tr>';
            }

            $output .= '<td class="' . ($isChild ? 'debug-bar-width30' : '') . '" style="--level: ' . $level . ';">' . ($hasChildren || $isQuery ? '<nav></nav>' : '') . $row['name'] . '</td>';
            $output .= '<td class="' . ($isChild ? 'debug-bar-width10' : '') . '">' . $row['component'] . '</td>';
            $output .= '<td class="' . ($isChild ? 'debug-bar-width10 ' : '') . 'debug-bar-alignRight">' . number_format($row['duration'] * 1000, 2) . ' ms</td>';
            $output .= "<td class='debug-bar-noverflow' colspan='{$segmentCount}'>";

            $offset = ((((float) $row['start'] - $startTime) * 1000) / $displayTime) * 100;
            $length = (((float) $row['duration'] * 1000) / $displayTime) * 100;

            $styles['debug-bar-timeline-' . $styleCount] = "left: {$offset}%; width: {$length}%;";

            $output .= "<span class='timer debug-bar-timeline-{$styleCount}' title='" . number_format($length, 2) . "%'></span>";
            $output .= '</td>';
            $output .= '</tr>';

            $styleCount++;

            // Ajouter des enfants le cas échéant
            if ($hasChildren || $isQuery) {
                $output .= '<tr class="child-row" id="timeline-' . ($styleCount - 1) . '_children" style="' . ($open ? '' : 'display: none;') . '">';
                $output .= '<td colspan="' . ($segmentCount + 3) . '" class="child-container">';
                $output .= '<table class="timeline">';
                $output .= '<tbody>';

                if ($isQuery) {
                    // Sortie de la chaîne de requête si requête
                    $output .= '<tr>';
                    $output .= '<td class="query-container" style="--level: ' . ($level + 1) . ';">' . $row['query'] . '</td>';
                    $output .= '</tr>';
                } else {
                    // Rendre récursivement les enfants
                    $output .= $this->renderTimelineRecursive($row['children'], $startTime, $segmentCount, $segmentDuration, $styles, $styleCount, $level + 1, true);
                }

                $output .= '</tbody>';
                $output .= '</table>';
                $output .= '</td>';
                $output .= '</tr>';
            }
        }

        return $output;
    }

    /**
     * Renvoie un tableau trié de tableaux de données chronologiques à partir des collecteurs.
     */
    protected function collectTimelineData($collectors): array
    {
        $data = [];

        // Le collecter
        foreach ($collectors as $collector) {
            if (! $collector['hasTimelineData']) {
                continue;
            }

            $data = array_merge($data, $collector['timelineData']);
        }

        // Le trier
        $sortArray = [
            array_column($data, 'start'), SORT_NUMERIC, SORT_ASC,
            array_column($data, 'duration'), SORT_NUMERIC, SORT_DESC,
            &$data,
        ];

        array_multisort(...$sortArray);

        // Ajouter une heure de fin à chaque élément
        array_walk($data, static function (&$row) {
            $row['end'] = $row['start'] + $row['duration'];
        });

        // Le grouper
        $data = $this->structureTimelineData($data);

        return $data;
    }

    /**
     * Organise les données de chronologie déjà triées dans une structure parent => enfant.
     */
    protected function structureTimelineData(array $elements): array
    {
        // Nous nous définissons comme le premier élément du tableau
        $element = array_shift($elements);

        // Si nous avons des enfants derrière nous, récupérez-les et attachez-les-nous
        while (! empty($elements) && $elements[array_key_first($elements)]['end'] <= $element['end']) {
            $element['children'][] = array_shift($elements);
        }

        // Assurez-vous que nos enfants sachent s'ils ont eux aussi des enfants
        if (isset($element['children'])) {
            $element['children'] = $this->structureTimelineData($element['children']);
        }

        // Si nous n'avons pas de frères et sœurs plus jeunes, nous pouvons revenir
        if (empty($elements)) {
            return [$element];
        }

        // Assurez-vous que nos jeunes frères et sœurs connaissent également leurs proches
        return array_merge([$element], $this->structureTimelineData($elements));
    }

    /**
     * Renvoie un tableau de données de tous les modules
     * qui devrait être affiché dans l'onglet 'Vars'.
     */
    protected function collectVarData(): array
    {
        if (! ($this->config->collectVarData ?? true)) {
            return [];
        }

        $data = [];

        foreach ($this->collectors as $collector) {
            if (! $collector->hasVarData()) {
                continue;
            }

            $data = array_merge($data, $collector->getVarData());
        }

        return $data;
    }

    /**
     * Arrondit un nombre à la valeur incrémentielle la plus proche.
     */
    protected function roundTo(float $number, int $increments = 5): float
    {
        $increments = 1 / $increments;

        return ceil($number * $increments) / $increments;
    }

    /**
     * Préparez-vous au débogage..
     */
    public function prepare(array $stats, ?RequestInterface $request = null, ?ResponseInterface $response = null): ResponseInterface
    {
		$request ??= Services::request();
		$response ??= Services::response();

		// Si on est en CLI ou en prod, pas la peine de continuer car la debugbar n'est pas utilisable dans ces environnements
		if (is_cli() || on_prod()) {
			return $response;
		}

		// Si on a desactiver le debogage ou l'affichage de la debugbar, on s'arrete
		if (! BLITZ_DEBUG || ! $this->config->show_debugbar) {
			return $response;
		}
		
		$toolbar = Services::toolbar($this->config);
		$data    = $toolbar->run(
			$stats['startTime'],
			$stats['totalTime'],
			$request,
			$response
		);

		// Mise à jour vers microtime() pour que nous puissions obtenir l'historique
		$time = sprintf('%.6f', microtime(true));

		if (! is_dir($this->debugPath)) {
			mkdir($this->debugPath, 0777);
		}

		$this->writeFile($this->debugPath . '/debugbar_' . $time . '.json', $data, 'w+');

		$format = $response->getHeaderLine('content-type');

		// Les formats non HTML ne doivent pas inclure la barre de débogage, 
		// puis nous envoyons des en-têtes indiquant où trouver les données de débogage pour cette réponse
		if ($request->isAJAX() || strpos($format, 'html') === false) {
			return $response
				->withHeader('Debugbar-Time', "{$time}")
				->withHeader('Debugbar-Link', site_url("?debugbar_time={$time}"));
		}

		$_SESSION['_blitz_debugbar_'] = array_merge($_SESSION['_blitz_debugbar_'] ?? [], compact('time'));

		$debugRenderer = $this->respond();

		// Extract css
		preg_match('/<style (?:.+)>(.+)<\/style>/', $debugRenderer, $matches);
		$style = $matches[0] ?? '';
        $debugRenderer = str_replace($style, '', $debugRenderer);
	
        // Extract js
		preg_match('/<script (?:.+)>(.+)<\/script>/', $debugRenderer, $matches);
		$js = $matches[0] ?? '';
		$debugRenderer = str_replace($js, '', $debugRenderer);
	
		$responseContent = $response->getBody()->getContents();
		
		if (strpos($responseContent, '<head>') !== false) {
			$responseContent = preg_replace('/<head>/', '<head>' . $style, $responseContent, 1);
		}
		else {
			$responseContent .= $style;
		}

        if (strpos($responseContent, '</body>') !== false) {
			$responseContent = preg_replace('/<\/body>/', '<div id="toolbarContainer">'.trim(preg_replace('/\s+/', ' ', $debugRenderer)).'</div>'.$js.'<script>ciDebugBar.init();</script></body>', $responseContent, 1);
		}
		else {
			$responseContent .= '<div id="toolbarContainer">'.trim(preg_replace('/\s+/', ' ', $debugRenderer)).'</div>'.$js.'<script>ciDebugBar.init();</script>';
		}

		return $response->withBody(
			Utils::streamFor($responseContent)
		);
    }

    /**
     * Injectez la barre d'outils de débogage dans la réponse.
     *
	 * @return string
	 * 
     * @codeCoverageIgnore
     */
    public function respond()
    {
        if (on_test()) {
            return '';
        }

        $request = Services::request();

        // Si la requête contient '?debugbar alors nous sommes
        // renvoie simplement le script de chargement
        if ($request->getQuery('debugbar') !== null) {
            header('Content-Type: application/javascript');

            ob_start();
            include $this->config->view_path . 'toolbarloader.js';
            $output = ob_get_clean();
            $output = str_replace('{url}', rtrim(site_url(), '/'), $output);
            
			return $output;
        }

        // Sinon, s'il inclut ?debugbar_time, alors
        // nous devrions retourner la barre de débogage entière.
		$debugbarTime = $_SESSION['_blitz_debugbar_']['time'] ?? $request->getQuery('debugbar_time');
        if ($debugbarTime) {
            // Négociation du type de contenu pour formater la sortie
            $format = $request->negotiate('media', ['text/html', 'application/json', 'application/xml']);
            $format = explode('/', $format)[1];

            $filename = 'debugbar_' . $debugbarTime;
            $filename = $this->debugPath . DS . $filename . '.json';

            if (is_file($filename)) {
                // Affiche la barre d'outils si elle existe
                return $this->format($debugbarTime, file_get_contents($filename), $format);
            }

            // Nom de fichier introuvable
            http_response_code(404);

            exit; // Quitter ici est nécessaire pour éviter de charger la page d'index
        }

		return '';
    }

    /**
     * Formatte la sortie
     */
    protected function format(int $debugbar_time, string $data, string $format = 'html'): string
    {
        $data = json_decode($data, true);

        if ($this->config->max_history !== 0 && preg_match('/\d+\.\d{6}/s', (string) $debugbar_time, $debugbarTime)) {
            $history = new HistoryCollector();
            $history->setFiles(
                $debugbarTime[0],
                $this->config->max_history
            );

            $data['collectors'][] = $history->getAsArray();
        }
        $output = '';

        switch ($format) {
            case 'html':
                $data['styles'] = [];
                extract($data);
				$parser = new Parser([], $this->config->view_path);
                // $parser = Services::parser($this->config->view_path, null, false);
                ob_start();
                include rtrim($this->config->view_path, '/\\') . DS . 'toolbar.tpl.php';
                $output = ob_get_clean();
                break;

            case 'json':
                $formatter = new JsonFormatter();
                $output    = $formatter->format($data);
                break;

            case 'xml':
                $formatter = new XmlFormatter();
                $output    = $formatter->format($data);
                break;
        }

        return $output;
    }

	/**
	 * Écrit des données dans le fichier spécifié dans le chemin.
	 * Crée un nouveau fichier s'il n'existe pas.
	 */
	protected function writeFile(string $path, string $data, string $mode = 'wb'): bool
	{
		try {
			$fp = fopen($path, $mode);

			flock($fp, LOCK_EX);

			for ($result = $written = 0, $length = strlen($data); $written < $length; $written += $result) {
				if (($result = fwrite($fp, substr($data, $written))) === false) {
					break;
				}
			}

			flock($fp, LOCK_UN);
			fclose($fp);

			return is_int($result);
		}
		catch (\Exception $fe) {
			return false;
		}
	}
}
