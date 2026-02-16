<?php

/**
 * This file is part of Blitz PHP framework.
 *
 * (c) 2022 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Spec\BlitzPHP\App\Controllers;

use BlitzPHP\Controllers\RestController as BaseController;
use Psr\Http\Message\ResponseInterface;

class RestController extends BaseController
{
	public $before;
	public $after;


    // Méthodes accessibles pour les tests
	public function testRemap($method, $params = []) {
		return $this->_remap($method, $params);
	}

	public function testHandleException($ex) {
		return $this->handleException($ex);
	}

	public function testValidateRequest() {
		return $this->validateRequest();
	}

	public function testValidateAjaxOnly() {
		return $this->validateAjaxOnly();
	}

	public function testValidateHttps() {
		return $this->validateHttps();
	}

	public function testValidateIpBlacklist() {
		return $this->validateIpBlacklist();
	}

	public function testValidateIpWhitelist() {
		return $this->validateIpWhitelist();
	}

	public function testAjaxOnly() {
		return $this->ajaxOnly();
	}

	public function testReturnFormat($format) {
		return $this->returnFormat($format);
	}

	public function testRequireHttps() {
		return $this->requireHttps();
	}

	public function testIpBlacklist(...$ips) {
		return $this->ipBlacklist(...$ips);
	}

	public function testIpWhitelist(...$ips) {
		return $this->ipWhitelist(...$ips);
	}

	public function testRespond($data, $status = null) {
		return $this->respond($data, $status);
	}

	public function testRespondFail($message = null, $status = null, $code = null, $errors = []) {
		return $this->respondFail($message, $status, $code, $errors);
	}

	public function testRespondSuccess($message = null, $result = null, $status = null) {
		return $this->respondSuccess($message, $result, $status);
	}

	public function testFormatResult($result) {
		return $this->formatResult($result);
	}

	public function testFormatEntity($element) {
		return $this->formatEntity($element);
	}

	public function testFormatResponse($data) {
		return $this->formatResponse($data);
	}

	public function testLang($line, $args = []) {
		return $this->lang($line, $args);
	}

	public function testTranslate($line, $args = []) {
		return $this->_translate($line, $args);
	}

	public function testBefore($method, $params) {
		return $this->before($method, $params);
	}

	public function testAfter($method, $params, $response) {
		return $this->after($method, $params, $response);
	}

	// Méthode de test pour _remap
	public function testMethod() {
		return 'test response';
	}

	protected function before($method, $params): ?ResponseInterface {
		return $this->before;
	}
}
