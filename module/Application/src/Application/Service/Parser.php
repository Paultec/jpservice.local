<?php
namespace Application\Service;

use Zend\Http\Client as HttpClient;
use Zend\Dom\Document;
use Zend\Dom\Document\Query;

class Parser
{
    public function parse($uri)
    {
        $client   = new HttpClient($uri);
        $response = $client->send();

        $html     = $response->getBody();
        $document = new Document($html);

        if (strpos($uri, 'aliexpress.com') !== false) {
            $image       = '.ui-image-viewer-thumb-frame img';
            $title       = '.main-wrap .product-name';
            $description = '.product-desc .ui-box-body';
            $price       = '#product-price b';
        } elseif (strpos($uri, 'amazon.com') !== false) {
            $image       = '#imgTagWrapperId img';
            $title       = '#title #productTitle';
            $description = '#feature-bullets .a-vertical';
            $price       = '#priceblock_ourprice';
        } elseif (strpos($uri, 'ebay.com') !== false) {
            $image       = '#mainImgHldr #icImg';
            $title       = '#itemTitle';
            $description = '#itemTitle'; // title
            $price       = '#prcIsum';
        } else {
            return 'Не поддерживается в beta версии';
        }

        $tmpArr = [];

        $tmpArr['image']       = trim(strip_tags(Query::execute($image, $document, Query::TYPE_CSS)[0]->getAttribute('src')));
        $tmpArr['title']       = trim(strip_tags(Query::execute($title, $document, Query::TYPE_CSS)[0]->nodeValue));
        $tmpArr['description'] = trim(strip_tags(Query::execute($description, $document, Query::TYPE_CSS)[0]->nodeValue));
        $tmpArr['price']       = trim(strip_tags(Query::execute($price, $document, Query::TYPE_CSS)[0]->nodeValue));
        $tmpArr['source']      = $uri;

        return $tmpArr;
    }
}