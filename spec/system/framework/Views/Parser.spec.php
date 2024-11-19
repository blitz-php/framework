<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use BlitzPHP\Container\Services;
use BlitzPHP\View\Parser;

describe('Views / Parser', function (): void {
    beforeAll(function (): void {
        $this->parser = new Parser(config('view'), Services::locator());
    });

	afterEach(function (): void {
		$this->parser->resetData();
	});

	it('Delimiters', function (): void {
		// S'assurer que les délimiteurs par défaut sont présents
        expect($this->parser->leftDelimiter)->toBe('{');
        expect($this->parser->rightDelimiter)->toBe('}');

		// Les remplacer par des crochets
		$this->parser->setDelimiters('[', ']');

		// S'assurer qu'ils ont changé
        expect($this->parser->leftDelimiter)->toBe('[');
		expect($this->parser->rightDelimiter)->toBe(']');

		// Les réinitialiser
		$this->parser->setDelimiters();

		// S'assurer que les délimiteurs par défaut sont présents
        expect($this->parser->leftDelimiter)->toBe('{');
		expect($this->parser->rightDelimiter)->toBe('}');
	});

	it('Parse un template', function (): void {
		$this->parser->setVar('teststring', 'Hello World');
        expect($this->parser->render('template1'))->toBe("<h1>Hello World</h1>\n");
	});

	it('Parse une chaine de caractere', function (): void {
		$data = [
            'title' => 'Page Title',
            'body'  => 'Lorem ipsum dolor sit amet.',
        ];

        $template = "{title}\n{body}";

        $result = implode("\n", $data);

        $this->parser->setData($data);
        expect($this->parser->renderString($template))->toBe($result);
	});

	it('Parse une chaine de caractere avec des donnees manquantes', function (): void {
		$data = [
            'title' => 'Page Title',
            'body'  => 'Lorem ipsum dolor sit amet.',
        ];

        $template = "{title}\n{body}\n{name}";

        $result = implode("\n", $data) . "\n{name}";

        $this->parser->setData($data);
        expect($this->parser->renderString($template))->toBe($result);
	});

	it('Parse une chaine de caractere avec des donnees non utilisées', function (): void {
		$data = [
            'title' => 'Page Title',
            'body'  => 'Lorem ipsum dolor sit amet.',
            'name'  => 'Someone',
        ];

        $template = "{title}\n{body}";

        $result = "Page Title\nLorem ipsum dolor sit amet.";

        $this->parser->setData($data);
		expect($this->parser->renderString($template))->toBe($result);
	});

	it('Pas de template', function (): void {
		expect($this->parser->renderString(''))->toBe('');
	});

	it('Parse un simple tableau', function (): void {
		$data = [
            'title'  => 'Super Heroes',
            'powers' => [
                [
                    'invisibility' => 'yes',
                    'flying'       => 'no',
                ],
            ],
        ];

        $template = "{title}\n{powers}{invisibility}\n{flying}{/powers}";

        $this->parser->setData($data);
		expect($this->parser->renderString($template))->toBe("Super Heroes\nyes\nno");
	});

	it('Parse un tableau mutidimentionnel', function (): void {
		$data = [
            'powers' => [
                [
                    'invisibility' => 'yes',
                    'flying'       => 'no',
                ],
            ],
        ];

        $template = "{powers}{invisibility}\n{flying}{/powers}\nsecond:{powers} {invisibility} {flying}{ /powers}";

        $this->parser->setData($data);
		expect($this->parser->renderString($template))->toBe("yes\nno\nsecond: yes no");
	});

	it('Parse un tableau de tableau', function (): void {
		$data = [
            'title'  => 'Super Heroes',
            'powers' => [
                [
                    'invisibility' => 'yes',
                    'flying'       => [
                        [
                            'by'     => 'plane',
                            'with'   => 'broomstick',
                            'scared' => 'yes',
                        ],
                    ],
                ],
            ],
        ];

        $template = "{title}\n{powers}{invisibility}\n{flying}{by} {with}{/flying}{/powers}";

        $this->parser->setData($data);
		expect($this->parser->renderString($template))->toBe("Super Heroes\nyes\nplane broomstick");
	});

	it('Parse un tableau d\'objet', function (): void {
		$eagle       = new stdClass();
        $eagle->name = 'Baldy';
        $eagle->home = 'Rockies';

        $data = [
            'birds' => [
                [
                    'pop'  => $eagle,
                    'mom'  => 'Owl',
                    'kids' => [
                        'Tom',
                        'Dick',
                        'Harry',
                    ],
                    'home' => opendir('.'),
                ],
            ],
        ];

        $template = '{birds}{mom} and {pop} work at {home}{/birds}';

        $this->parser->setData($data);
		expect($this->parser->renderString($template))->toBe('Owl and Class: stdClass work at Resource');
	});

	it('Parse les bloucles', function (): void {
		$data = [
            'title'  => 'Super Heroes',
            'powers' => [
                ['name' => 'Tom'],
                ['name' => 'Dick'],
                ['name' => 'Henry'],
            ],
        ];

        $template = "{title}\n{powers}{name} {/powers}";

        $this->parser->setData($data);
		expect($this->parser->renderString($template))->toBe("Super Heroes\nTom Dick Henry ");
	});

	it('Parse les bloucles avec des parantheses', function (): void {
		$data = [
            'title'  => 'Super Heroes',
            'powers' => [
                ['name' => 'Tom'],
                ['name' => 'Dick'],
                ['name' => 'Henry'],
            ],
        ];

        $template = "{title}\n{powers}({name}) {/powers}";

        $this->parser->setData($data);
		expect($this->parser->renderString($template))->toBe("Super Heroes\n(Tom) (Dick) (Henry) ");
	});

	it("Parse les bloucles d'objets", function (): void {
		$obj1 = new stdClass();
        $obj2 = new stdClass();
        $obj3 = new stdClass();

        $obj1->name = 'Tom';
        $obj2->name = 'Dick';
        $obj3->name = 'Henry';

        $data = [
            'title'  => 'Super Heroes',
            'powers' => [
                $obj1,
                $obj2,
                $obj3,
            ],
        ];

        $template = "{title}\n{powers}{name} {/powers}";

        $this->parser->setData($data);
		expect($this->parser->renderString($template))->toBe("Super Heroes\nTom Dick Henry ");
	});

	it("Parse un tableau ayant les memes cles dans un sous-tableau", function (): void {
		$data = [
            'type'   => 'Super Powers',
            'powers' => [
                [
                    'type' => 'invisibility',
                ],
            ],
        ];

        $template = '{type} like {powers}{type}{/powers}';

        $this->parser->setData($data);
		expect($this->parser->renderString($template))->toBe('Super Powers like invisibility');
	});

	it("Parse un tableau ayant les memes cles dans un sous-tableau imbriqué", function (): void {
		$data = [
            'title'   => 'My title',
            'similar' => [
                ['items' => [
                    [
                        'title' => 'My similar title',
                    ],
                ],
                ],
            ],
        ];

        $template = '{title} with similar item {similar}{items}{title}{/items}{/similar}';

        $this->parser->setData($data);
		expect($this->parser->renderString($template))->toBe('My title with similar item My similar title');
	});
});
