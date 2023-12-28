<?php
/**
 * @var BlitzPHP\Debug\Toolbar $this
 * @var int                    $totalTime
 * @var int                    $totalMemory
 * @var string                 $url
 * @var string                 $method
 * @var bool                   $isAJAX
 * @var int                    $startTime
 * @var int                    $totalTime
 * @var int                    $totalMemory
 * @var float                  $segmentDuration
 * @var int                    $segmentCount
 * @var string                 $blitzVersion
 * @var array                  $collectors
 * @var array                  $vars
 * @var array                  $styles
 * @var BlitzPHP\View\Parser   $parser
 */
?>
<style type="text/css">/* BlitzPHP - Debug bar ======== Credit: CodeIgniter - https://codeigniter.com */<?= preg_replace('#[\r\n\t ]+#', ' ', file_get_contents(__DIR__ . '/toolbar.css')) ?></style>
<script id="toolbar_js" type="text/javascript">/* BlitzPHP - Debug bar ======== Credit: CodeIgniter - https://codeigniter.com */<?= file_get_contents(__DIR__ . '/toolbar-min.js') ?></script>

<!-- BlitzPHP - Debug bar ======== Credit: CodeIgniter - https://codeigniter.com  -->
<div id="debug-icon" class="debug-bar-ndisplay">
    <a id="debug-icon-link" href="javascript:void(0)">
    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
			x="0px" y="0px" width="200px" height="200px" viewBox="0 0 200 200" preserveAspectRatio="xMidYMid meet" enable-background="new 0 0 175 173" xml:space="preserve">
        <image width="200" height="200" x="0" y="0" xlink:href="data:image/png;base64,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" />
		</svg>
    </a>
</div>
<div id="debug-bar">
    <div class="toolbar">
        <span id="toolbar-position"><a href="javascript: void(0)">&#8597;</a></span>
        <span id="toolbar-theme"><a href="javascript: void(0)">&#128261;</a></span>
        <span class="blitzphp-label">
            <a href="javascript: void(0)" data-tab="blitzphp-timeline">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAD7SURBVEhLY6ArSEtLK09NTbWHcvGC9PR0BaDaQiAdUl9fzwQVxg+AFvwHamqHcnGCpKQkeaDa9yD1UD09UCn8AKaBWJySkmIApFehi0ONwwRQBceBLurAh4FqFoHUAtkrgPgREN+ByYEw1DhMANVEMIhAYQ5U1wtU/wmILwLZRlAp/IBYC8gGw88CaFj3A/FnIL4ETDXGUCnyANSC/UC6HIpnQMXAqQXIvo0khxNDjcMEQEmU9AzDuNI7Lgw1DhOAJIEuhQcRKMcC+e+QNHdDpcgD6BaAANSSQqBcENFlDi6AzQKqgkFlwWhxjVI8o2OgmkFaXI8CTMDAAAAxd1O4FzLMaAAAAABJRU5ErkJggg==">
                <span class="hide-sm"><?= $totalTime ?> ms &nbsp; <?= $totalMemory ?> MB</span>
            </a>
        </span>

        <?php foreach ($collectors as $c) : ?>
            <?php if (! $c['isEmpty'] && ($c['hasTabContent'] || $c['hasLabel'])) : ?>
                <span class="blitzphp-label">
                    <a href="javascript: void(0)" data-tab="blitzphp-<?= $c['key'] ?>">
                        <img src="<?= $c['icon'] ?>">
                        <span class="hide-sm">
                            <?= $c['title'] ?>
                            <?php if ($c['badgeValue'] !== null) : ?>
                                <span class="badge"><?= $c['badgeValue'] ?></span>
                            <?php endif ?>
                        </span>
                    </a>
                </span>
            <?php endif ?>
        <?php endforeach ?>

        <span class="blitzphp-label">
            <a href="javascript: void(0)" data-tab="blitzphp-vars">
                <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAACLSURBVEhLYxgFJIHU1NSraWlp/6H4T0pKSjRUijoAyXAwBlrYDpViAFpmARQrJwZDtWACoCROC4D8CnR5XBiqBRMADfyNprgRKkUdAApzoCUdUNwE5MtApYYIALp6NBWBMVQLJgAaOJqK8AOgq+mSio6DggjEBtLUT0UwQ5HZIADkj6aiUTAggIEBANAEDa/lkCRlAAAAAElFTkSuQmCC">
                <span class="hide-sm">Vars</span>
            </a>
        </span>

        <h1>
            <span class="blitzphp-label">
                <a href="javascript: void(0)" data-tab="blitzphp-config">
				<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="25px" height="25px" viewBox="0 -6 20 20" preserveAspectRatio="xMidYMid meet">
						<image width="30" height="30" x="-5" y="-10" xlink:href="data:image/png;base64,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" />
					</svg>
                    <?= $blitzVersion ?>
                </a>
            </span>
        </h1>

        <!-- Open/Close Toggle -->
        <a id="debug-bar-link" href="javascript:void(0)" title="Open/Close">
            <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAEPSURBVEhL7ZVLDoJAEEThRuoGDwSEG+jCuFU34s3AK3APP1VDDSGMqI1xx0s6M/2rnlHEaMZElmWrPM+vsDvsYbQ7+us0TReSC2EBrEHxCevRYuppYLXkQpC8sVCuGfTvqSE3hFdFwUGuGfRvqSE35NUAfKZrbQNQm2jrMA+gOK+M+FmhDsRL5voHMA8gFGecq0JOXLWlQg7E7AMIxZnjOiZOEJ82gFCcedUE4gS56QP8yf8ywItz7e+RituKlkkDBoIOH4Nd4HZD4NsGYJ/Abn1xEVOcuZ8f0zc/tHiYmzTAwscBvDIK/veyQ9K/rnewjdF26q0kF1IUxZIFPAVW98x/a+qp8L2M/+HMhETRE6S8TxpZ7KGXAAAAAElFTkSuQmCC">
        </a>
    </div>

    <!-- Timeline -->
    <div id="blitzphp-timeline" class="tab">
        <table class="timeline">
            <thead>
            <tr>
                <th class="debug-bar-width30">NOM</th>
                <th class="debug-bar-width10">COMPOSANT</th>
                <th class="debug-bar-width10">DUREE</th>
                <?php for ($i = 0; $i < $segmentCount; $i++) : ?>
                    <th><?= $i * $segmentDuration ?> ms</th>
                <?php endfor ?>
            </tr>
            </thead>
            <tbody>
            <?= $this->renderTimeline($collectors, $startTime, $segmentCount, $segmentDuration, $styles) ?>
            </tbody>
        </table>
    </div>

    <!-- Collector-provided Tabs -->
    <?php foreach ($collectors as $c) : ?>
        <?php if (! $c['isEmpty']) : ?>
            <?php if ($c['hasTabContent']) : ?>
                <div id="blitzphp-<?= $c['key'] ?>" class="tab">
                    <h2><?= $c['title'] ?> <span><?= $c['titleDetails'] ?></span></h2>

                    <?= is_string($c['display']) ? $c['display'] : $parser->setData($c['display'])->render("_{$c['key']}.tpl") ?>
                </div>
            <?php endif ?>
        <?php endif ?>
    <?php endforeach ?>

    <!-- In & Out -->
    <div id="blitzphp-vars" class="tab">

        <!-- VarData from Collectors -->
        <?php if (isset($vars['varData'])) : ?>
            <?php foreach ($vars['varData'] as $heading => $items) : ?>

                <a href="javascript:void(0)" onclick="blitzphpDebugBar.toggleDataTable('<?= strtolower(str_replace(' ', '-', $heading)) ?>'); return false;">
                    <h2><?= $heading ?></h2>
                </a>

                <?php if (is_array($items)) : ?>

                    <table id="<?= strtolower(str_replace(' ', '-', $heading . '_table')) ?>">
                        <tbody>
                        <?php foreach ($items as $key => $value) : ?>
                            <tr>
                                <td><?= $key ?></td>
                                <td><?= $value ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>

                <?php else: ?>
                    <p class="muted">Aucune donnée à afficher.</p>
                <?php endif ?>
            <?php endforeach ?>
        <?php endif ?>

        <!-- Session -->
        <a href="javascript:void(0)" onclick="blitzphpDebugBar.toggleDataTable('session'); return false;">
            <h2>Données utilisateur de session</h2>
        </a>

        <?php if (isset($vars['session'])) : ?>
            <?php if (! empty($vars['session'])) : ?>
                <table id="session_table">
                    <tbody>
                    <?php foreach ($vars['session'] as $key => $value) : ?>
                        <tr>
                            <td><?= $key ?></td>
                            <td><?= $value ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="muted">Aucune donnée à afficher.</p>
            <?php endif ?>
        <?php else : ?>
            <p class="muted">La session ne semble pas être active.</p>
        <?php endif ?>

        <h2>Requête <span>( <?= $vars['request'] ?> )</span></h2>

        <?php if (isset($vars['get']) && $get = $vars['get']) : ?>
            <a href="javascript:void(0)" onclick="blitzphpDebugBar.toggleDataTable('get'); return false;">
                <h3>$_GET</h3>
            </a>

            <table id="get_table">
                <tbody>
                <?php foreach ($get as $name => $value) : ?>
                    <tr>
                        <td><?= $name ?></td>
                        <td><?= $value ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>

        <?php if (isset($vars['post']) && $post = $vars['post']) : ?>
            <a href="javascript:void(0)" onclick="blitzphpDebugBar.toggleDataTable('post'); return false;">
                <h3>$_POST</h3>
            </a>

            <table id="post_table">
                <tbody>
                <?php foreach ($post as $name => $value) : ?>
                    <tr>
                        <td><?= $name ?></td>
                        <td><?= $value ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>

        <?php if (isset($vars['headers']) && $headers = $vars['headers']) : ?>
            <a href="javascript:void(0)" onclick="blitzphpDebugBar.toggleDataTable('request_headers'); return false;">
                <h3>Headers</h3>
            </a>

            <table id="request_headers_table">
                <tbody>
                <?php foreach ($headers as $header => $value) : ?>
                    <tr>
                        <td><?= $header ?></td>
                        <td><?= $value ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>

        <?php if (isset($vars['cookies']) && $cookies = $vars['cookies']) : ?>
            <a href="javascript:void(0)" onclick="blitzphpDebugBar.toggleDataTable('cookie'); return false;">
                <h3>Cookies</h3>
            </a>

            <table id="cookie_table">
                <tbody>
                <?php foreach ($cookies as $name => $value) : ?>
                    <tr>
                        <td><?= $name ?></td>
                        <td><?= is_array($value) ? print_r($value, true) : $value ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>

        <h2>Reponse
            <span>( <?= $vars['response']['statusCode'] . ' - ' . $vars['response']['reason'] ?> )</span>
        </h2>

        <?php if (isset($vars['response']['headers']) && $headers = $vars['response']['headers']) : ?>
            <a href="javascript:void(0)" onclick="blitzphpDebugBar.toggleDataTable('response_headers'); return false;">
                <h3>Headers</h3>
            </a>

            <table id="response_headers_table">
                <tbody>
                <?php foreach ($headers as $header => $value) : ?>
                    <tr>
                        <td><?= $header ?></td>
                        <td><?= $value ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>

    <!-- Config Values -->
    <div id="blitzphp-config" class="tab">
        <h2>Configuration du système</h2>

        <?= $parser->setData($config)->render('_config.tpl') ?>
    </div>
</div>

<style type="text/css"><?php foreach ($styles as $name => $style): ?><?= sprintf('.%s { %s }', $name, $style) ?><?php endforeach ?></style>
