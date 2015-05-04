<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Http\Client as HttpClient;
use Zend\Dom\Document;
use Zend\Dom\Document\Query;

class IndexController extends AbstractActionController
{
    /**
     * @var array
     * список тэгов для парсера, которые нужно разбирать
     */
    protected $targetTags = [
        //'<a',
        '<article',
        '<b',
        '<big',
        '<blockquote',
        '<datalist',
        '<dd',
        '<details',
        '<div',
        '<dl',
        '<dt',
        '<h1',
        '<h2',
        '<h3',
        '<h4',
        '<h5',
        '<h6',
        '<img',
        '<label',
        '<legend',
        '<li',
        '<main',
        '<ol',
        '<option',
        '<p',
        '<plaintext',
        //'<param',
        '<pre',
        '<summary',
        '<span',
        '<table',
        '<tbody',
        '<td',
        '<textarea',
        '<tfoot',
        '<th',
        '<thead',
        '<title',
        '<tr',
        '<tt',
        '<ul',
        '<u',
    ];

    /**
     * @var array
     * список атрибутов тэгов для парсера
     */
    protected $attributeList = [
        'class'     => null,
        'hidden'    => null,
        'id'        => null,
        'style'     => null,
        'title'     => null,
        'value'     => null,
    ];

    /**
     * @var array
     *  массив, в котором каждый элемент содержит: название тэга, его атрибуты со значениями и текстовую информацию
     */
    protected $textContent = [];

    /**
     * @var array
     * массив со всеми тэгами IMG
     */
    protected $imgContent = [];

    /**
     * @var string
     */
    protected $pageContent = '';

    public function indexAction()
    {
        $client = new HttpClient();
        $client->setAdapter('Zend\Http\Client\Adapter\Curl');

        //$targetPage = 'http://www.grandua.com.ua/p/2342-platie_fantaziya_molochnoie.html';
        //$targetPage = 'http://www.crumina.ua/product/391/%D0%92%D0%B5%D1%81%D0%BD%D0%B0-%D0%9E%D1%81%D0%B5%D0%BD%D1%8C/%D0%BC%D0%BE%D0%B4%D0%B5%D0%BB%D1%8C-7062-%D0%B4%D0%B6%D0%B8%D0%BD%D1%81/';
        //$targetPage = 'http://100chehlov.com.ua/dlya-noutbukov/sumki-dlya-noutbukov-ogio/sumka-dlya-noutbuka-ogio-17-renegade-rss-black-pindot-111071-317-detail';
        //$targetPage = 'http://klevo.com.ua/product/udilische-spinningovoe-abu-garcia-rod-vendetta-703-5_15-spin-sht.html';
        //$targetPage = 'http://dieline-genius.com/customize/3/';
        //$targetPage = 'http://www.6pm.com/crocs-crocband-jaunt-navy';
        $targetPage = 'http://group.aliexpress.com/259417012-1443375516-detail.html';

        $client->setUri($targetPage);

        $result = $client->send();

        $this->pageContent = $result->getBody();

        // Начало парсера html-страницы
        while ($this->pageContent) {

            // Разбираем страницу на отдельные строки, используя '<' как разделитель, начиная с конца
            $currentLine = strrchr($this->pageContent, '<');

            // Удаляем из конца исходной строки ту ее часть ( $currentLine ), которую выделили в предыдущем шаге
            $posCurrentLine     = strrpos($this->pageContent, $currentLine);
            $this->pageContent  = trim(substr($this->pageContent, 0, $posCurrentLine));

            // Если в начале строки НЕТ '</' (т.е. это не закрывающий тэг) - делаем разбор этой строки, иначе - продолжаем разбор исходной страницы
            if (false === (strpos($currentLine, '</'))) {

                // Выбираем из начала строки название текущего тэга
                $tagName = strstr($currentLine, ' ', true);

                // Если этот тэг входит в список тэгов для парсинга - продолжаем разбор строки, иначе - переходим к продолжению разбора страницы
                if (in_array($tagName, $this->targetTags)) {

                    // Если после закрывающей скобки есть какой-либо текст, начинаем разбор текущей строки, иначе - переходим к проверке тэга на изображение
                    if (strrchr($currentLine, '>') !== '>') {

                        // Выделяем и записываем название текущего тэга
                        $currentTag = substr($currentLine, 1, (strlen($tagName) - 1));

                        // Выделяем и записываем текст, следующий за закрывающей скобкой '>'
                        $currentText = trim(preg_replace('/\s{2,}/', ' ',substr(strrchr($currentLine, '>'), 1)));

                        // Выделяем и записываем список всех атрибутов и их значений, удаляя из текущей строки название тэга и текст, который был после закрывающей скобки '>'
                        $currentAttributes = substr($currentLine, (strlen($tagName) + 1), (strlen($currentLine) - strlen($currentTag) - strlen($currentText) - 3));

                        // Записываем все символы из списка атрибутов и их значений
                        $listSymbols = str_split($currentAttributes);

                        // Находим позиции, на которых находятся символы '=' и '"'
                        $posEqual   = array_keys($listSymbols, '=');
                        $posQuotes  = array_keys($listSymbols, '"');

                        $attributeList = $this->attributeList;

                        // Если в списке атрибутов есть хотя бы один символ '=' и более одного символа '"', начинаем искать названия атрибутов и их значения
                        if ((count($posEqual) > 0) && (count($posQuotes) > 1)) {

                            // Устанавливаем флаг первого прохода по списку атрибутов
                            $itFirstAttr = true;

                            // Продолжаем искать названия атрибутов и их значения, пока в списке атрибутов есть хотя бы один символ '=' и более одного символа '"'
                            while (count($posEqual) && (count($posQuotes) > 1)) {

                                // Извлекаем позицию 1-го символа '=', а также 1-го и 2-го символов '"'
                                $currentEqual   = array_shift($posEqual);
                                $currentQuotes1 = array_shift($posQuotes);
                                // Если мы в цикле в 1-й раз, присваиваем переменной prevQuotes2 = 0, иначе присваиваем ей позицию 2-й кавычки из предыдущего шага +1
                                $prevQuotes2    = $itFirstAttr ? 0 : ($currentQuotes2 + 1);
                                $currentQuotes2 = array_shift($posQuotes);

                                // Устанавливаем стартовую позицию для поиска названия атрибута
                                $startPosAttrName = $prevQuotes2;

                                // Выделяем и записываем название атрибута и его значение
                                $currentAttr        = trim(substr($currentAttributes, $startPosAttrName, ($currentEqual - $startPosAttrName)));
                                $currentAttrValue   = trim(substr($currentAttributes, ($currentQuotes1 + 1), ($currentQuotes2 - $currentQuotes1 - 1)));

                                // Если название атрибута есть в списке интересующих нас атрибутов, записываем его со значением
                                if (array_key_exists($currentAttr, $this->attributeList)) {
                                    $attributeList[$currentAttr] = $currentAttrValue;
                                }

                                // Переключаем флаг и продолжаем разбор строки списка атрибутов
                                $itFirstAttr = false;
                            }
                        }

                        // Записываем спарсенную текстовую информацию
                        $this->textContent[] = [
                            'tagName'       => $currentTag,
                            'attributeList' => $attributeList,
                            'text'          => $currentText,
                        ];
                    }

                    // Если это картинка - записываем данные
                    if ($tagName === '<img') {
                        $this->imgContent[] = $currentLine;
                    }
                }
            }
        }

        // Реверсируем массив, т.к. разбор начинали с конца страницы
        $this->textContent = array_reverse($this->textContent);

        // Конец парсера html-страницы

        var_dump($this->textContent);
        //var_dump($this->imgContent);

        return new ViewModel();
    }
}
