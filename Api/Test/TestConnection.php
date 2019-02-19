<?php
/**
 * Copyright (c) 2016 Martin Aarhof
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace SDM\Valitor\Api\Test;

use SDM\Valitor\AbstractApi;
use SDM\Valitor\Authentication;
use SDM\Valitor\Exceptions\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * This method requires no authentication and is presented for your system to test the connection to our system.
 */
class TestConnection extends AbstractApi
{

    /**
     * TestConnection constructor.
     * @param string $baseUrl
     */
    public function __construct($baseUrl = null)
    {
        $auth = new Authentication('', '', null);
        parent::__construct($auth);
        $this->baseUrl = $baseUrl;
    }

    /**
     * Is authentication required for this
     *
     * @return bool
     */
    protected function authRequired()
    {
        return false;
    }

    /**
     * Configure options
     *
     * @param OptionsResolver $resolver
     * @return void
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
    }

    /**
     * Url to api call
     *
     * @param array $options Resolved options
     * @return string
     */
    protected function getUrl(array $options)
    {
        return 'testConnection';
    }

    /**
     * Handle response
     *
     * @param Request $request
     * @param Response $response
     * @return true
     */
    protected function handleResponse(Request $request, Response $response)
    {
        return true;
    }

    /**
     * Handle exception response
     *
     * @param ClientException $exception
     * @return false
     */
    protected function handleExceptionResponse(ClientException $exception)
    {
        return false;
    }
}
