<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Autoloader\Autoloader;
use BlitzPHP\Autoloader\Locator;
use BlitzPHP\Enums\Method;
use Spec\BlitzPHP\App\Controllers\RestController;

use function Kahlan\expect;

describe('Autoloader / Locator', function(): void {
    beforeEach(function(): void {
        $this->autoloader = new Autoloader(config('autoload'));
        $this->autoloader->initialize();
        $this->autoloader->addNamespace([
            'Unknown'       => '/i/do/not/exist',
            'Tests/Support' => TEST_PATH . '_support/',
            'App'           => APP_PATH,
            'BlitzPHP'   => [
                TEST_PATH,
                SYST_PATH,
            ],
            'Errors'              => APP_PATH . 'Views/errors',
            'System'              => SUPPORT_PATH . 'Autoloader/system',
            'Acme\SampleProject' => TEST_PATH . '_support',
            'Acme\Sample'        => TEST_PATH . '_support/does/not/exists',
        ]);

        $this->locator = new Locator($this->autoloader);
    });

    describe('locateFile()', function(): void {
        context('avec des fichiers non-namespacés', function(): void {
            it('trouve un fichier dans le répertoire App', function(): void {
                $file = 'Controllers/HomeController';
                $expected = APP_PATH . 'Controllers' . DS . 'HomeController.php';

                expect($this->locator->locateFile($file))->toBe($expected);
            });

            it('retourne false quand un fichier non-namespacé n\'est pas trouvé', function(): void {
                $file = 'Unknown';
                expect($this->locator->locateFile($file))->toBe(false);
            });

            it('trouve un fichier avec un dossier dans le répertoire App', function(): void {
                $file = 'simple';
                $expected = VIEW_PATH . 'simple.php';

                expect($this->locator->locateFile($file, 'Views'))->toBe($expected);
            });

            it('trouve un fichier sans dossier dans le répertoire App', function(): void {
                $file = 'Common';
                $expected = APP_PATH . 'Common.php';
				file_put_contents($expected, '<?php ');

                expect($this->locator->locateFile($file))->toBe($expected);
				unlink($expected);
            });

            it('fonctionne dans un répertoire App imbriqué', function(): void {
                $file = 'Controllers/HomeController';
                $expected = CONTROLLER_PATH . 'HomeController.php';

                expect($this->locator->locateFile($file, 'Controllers'))->toBe($expected);
            });

            it('trouve un fichier avec le nom du dossier dans le chemin', function(): void {
                $file = 'Views/simple.php';
                $expected = VIEW_PATH . 'simple.php';

                expect($this->locator->locateFile($file, 'Views'))->toBe($expected);
            });
        });

        context('avec des fichiers namespacés', function(): void {
            it('trouve une vue namespacée', function(): void {
                $file = '\Errors\error_404';
                $expected = VIEW_PATH . 'errors' . DS . 'html' . DS . 'error_404.php';
				@mkdir(dirname($expected), recursive: true);
				file_put_contents($expected, '<?php ');

                expect($this->locator->locateFile($file, 'html'))->toBe($expected);
				unlink($expected);
            });

            it('trouve une vue namespacée imbriquée', function(): void {
                $file = '\Errors\html/error_404';
                $expected = VIEW_PATH . 'errors' .DS . 'html' . DS . 'error_404.php';
				@mkdir(dirname($expected), recursive: true);
				file_put_contents($expected, '<?php ');

                expect($this->locator->locateFile($file, 'html'))->toBe($expected);
				unlink($expected);
            });

            it('trouve un fichier avec un namespace correct', function(): void {
                $file = 'Acme\SampleProject\View\Views\simple';
                $expected = TEST_PATH . '_support' . DS . 'View' .DS . 'Views' . DS . 'simple.php';
				@mkdir(dirname($expected), recursive: true);
				file_put_contents($expected, '<?php ');

                expect($this->locator->locateFile($file, 'Views'))->toBe($expected);
				unlink($expected);
				});

            it('gère un fichier avec le nom du dossier dans un chemin namespacé', function(): void {
				$file = '\App\Views/errors/html/error_404.php';
                $expected = VIEW_PATH . 'errors' . DS . 'html' . DS . 'error_404.php';
				@mkdir(dirname($expected), recursive: true);
				file_put_contents($expected, '<?php ');

                expect($this->locator->locateFile($file, 'Views'))->toBe($expected);
				unlink($expected);
            });

            it('retourne false quand le fichier n\'existe pas dans un namespace existant', function(): void {
                $file = '\App\Views/unexistence-file.php';
                expect($this->locator->locateFile($file, 'Views'))->toBe(false);
            });

            it('retourne false quand le namespace n\'existe pas', function(): void {
                $file = '\Blogger\admin/posts.php';
                expect($this->locator->locateFile($file, 'Views'))->toBe(false);
            });
        });
    });

    describe('search()', function(): void {
        it('trouve un fichier avec une recherche simple', function(): void {
            $expected = CONFIG_PATH . 'app.php';
            $foundFiles = $this->locator->search('Config/app.php');

            expect($foundFiles[0])->toBe($expected);
        });

        it('trouve un fichier avec une extension spécifiée', function(): void {
            $expected = CONFIG_PATH . 'app.php';
            $foundFiles = $this->locator->search('Config/app', 'php');

            expect($foundFiles[0])->toBe($expected);
        });

        it('trouve plusieurs fichiers quand ils existent', function(): void {
            $foundFiles = $this->locator->search('Controllers/RestController', 'php');

            expect($foundFiles)->toContain(APP_PATH . 'Controllers' . DS . 'RestController.php');
            expect($foundFiles)->toContain(SYST_PATH . 'Controllers' . DS . 'RestController.php');
        });

        it('retourne un tableau vide quand le fichier n\'existe pas', function(): void {
            $foundFiles = $this->locator->search('Views/Fake.html');

            expect($foundFiles)->toBeEmpty();
        });

        it('priorise les fichiers système sur les fichiers app', function(): void {
            $foundFiles = $this->locator->search('Controllers/RestController', 'php', false);

            expect($foundFiles)->toBe([
                SYST_PATH . 'Controllers' . DS . 'RestController.php',
                APP_PATH . 'Controllers' . DS . 'RestController.php',
            ]);
        });
    });

    describe('listNamespaceFiles()', function(): void {
        it('retourne un tableau vide avec un préfixe et un chemin vides', function(): void {
            expect($this->locator->listNamespaceFiles('', ''))->toBeEmpty();
        });
    });

    describe('listFiles()', function(): void {
        it('liste les fichiers dans un répertoire', function(): void {
            $files = $this->locator->listFiles('Config/');

            $expectedWin = APP_PATH . 'Config\app.php';
            $expectedLin = APP_PATH . 'Config/app.php';

			expect(
				in_array($expectedWin, $files, true) ||
				in_array($expectedLin, $files, true)
			)->toBeTruthy();
        });

        it('ne contient pas de répertoires dans la liste des fichiers', function(): void {
            $files = $this->locator->listFiles('Views');
            $directory = str_replace('/', DIRECTORY_SEPARATOR, VIEW_PATH . 'Components');

            expect($files)->not->toContain($directory);
        });

        it('retourne un tableau vide quand l\'entrée est un fichier', function(): void {
            $files = $this->locator->listFiles('Config/app.php');

            expect($files)->toBeEmpty();
        });

        it('liste les fichiers depuis plusieurs répertoires', function(): void {
            $files = $this->locator->listFiles('Middlewares/');

            $expectedWin1 = SYST_PATH . 'Middlewares\BodyParser.php';
            $expectedLin1 = SYST_PATH . 'Middlewares/BodyParser.php';
            $expectedWin2 = APP_PATH . 'Middlewares\CustomMiddleware.php';
            $expectedLin2 = APP_PATH . 'Middlewares/CustomMiddleware.php';

			expect(
				(in_array($expectedWin1, $files, true) || in_array($expectedLin1, $files, true))
				&& (in_array($expectedWin2, $files, true) || in_array($expectedLin2, $files, true))
			)->toBeTruthy();
        });

        it('retourne un tableau vide quand le chemin n\'existe pas', function(): void {
            $files = $this->locator->listFiles('Fake/');

            expect($files)->toBeEmpty();
        });

        it('retourne un tableau vide sans chemin', function(): void {
            $files = $this->locator->listFiles('');

            expect($files)->toBeEmpty();
        });
    });

    describe('findQualifiedNameFromPath()', function(): void {
        it('trouve le nom qualifié depuis un chemin simple', function(): void {
            $className = $this->locator->findQualifiedNameFromPath(SYST_PATH . 'Enums/Method.php');
            $expected = Method::class;

            expect($className)->toBe($expected);
        });

        it('retourne false quand le fichier n\'existe pas', function(): void {
            $className = $this->locator->findQualifiedNameFromPath('modules/blog/Views/index.php');

            expect($className)->toBe(false);
        });

        it('retourne false sans namespace correspondant', function(): void {
            $className = $this->locator->findQualifiedNameFromPath('/etc/hosts');

            expect($className)->toBe(false);
        });
    });

    describe('getClassname()', function(): void {
        it('obtient le nom de la classe depuis un fichier de classe', function(): void {
            $className = $this->locator->getClassname(CONTROLLER_PATH . 'RestController.php');

            expect($className)->toBe(RestController::class);
        });

        it('retourne une chaîne vide depuis un fichier non-classe', function(): void {
            $className = $this->locator->getClassname(CONFIG_PATH . 'app.php');

            expect($className)->toBe('');
        });

        it('retourne une chaîne vide depuis un répertoire', function(): void {
            $className = $this->locator->getClassname(SYST_PATH);

            expect($className)->toBe('');
        });
    });
});
