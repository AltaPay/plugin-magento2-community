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

namespace SDM\Altapay\Api\Others;

use SDM\Altapay\AbstractApi;
use SDM\Altapay\Response\Embeds\Funding;
use SDM\Altapay\Traits\CsvToArrayTrait;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Used to get a comma separated value file containing the details of a funding.
 */
class FundingDownload extends AbstractApi
{
    use CsvToArrayTrait;

    /**
     * Set the download link from a funding object
     *
     * @param Funding $funding
     * @return $this
     */
    public function setFunding(Funding $funding)
    {
        $this->unresolvedOptions['link'] = $funding;
        return $this;
    }

    /**
     * Set the download link directly
     *
     * @param string $link
     * @return $this
     */
    public function setFundingDownloadLink($link)
    {
        $this->unresolvedOptions['link'] = $link;
        return $this;
    }

    /**
     * Configure options
     *
     * @param OptionsResolver $resolver
     * @return void
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['link']);
        $resolver->addAllowedTypes('link', ['string', Funding::class]);
        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer('link', function (Options $options, $value) {
            if ($value instanceof Funding) {
                return $value->DownloadLink;
            }
            return $value;
        });
    }

    /**
     * Handle response
     *
     * @param Request $request
     * @param Response $response
     * @return string
     */
    protected function handleResponse(Request $request, Response $response)
    {
        return (string) $response->getBody();
    }

    /**
     * Url to api call
     *
     * @param array $options Resolved options
     * @return string
     */
    protected function getUrl(array $options)
    {
        return $options['link'];
    }

    /**
     * Parse the URL
     *
     * @return string
     */
    protected function parseUrl()
    {
        return $this->getUrl($this->options);
    }
}
