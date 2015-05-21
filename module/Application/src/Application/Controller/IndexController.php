<?php

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Dom\Document;

use Application\Service\PreParser;
use Application\Service\Parser;

class IndexController extends AbstractActionController
{
    /**
     * Application\Service\PreParser
     */
    protected $preParser = null;

    /**
     * Application\Service\Parser
     */
    protected $parser = null;

    /**
     * @var array
     * список тэгов для парсера, которые нужно
     * разбирать
     */
    protected $targetTags = array(
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
    );

    /**
     * @var array
     * список атрибутов тэгов для парсера
     */
    protected $attributeList = array(
        'class' => null,
        'hidden' => null,
        'id' => null,
        'style' => null,
        'title' => null,
        'value' => null,
    );

    /**
     * @var array
     *  массив, в котором каждый элемент содержит:
     * название тэга, его атрибуты со значениями и
     * текстовую информацию
     */
    protected $textContent = array(
        
    );

    /**
     * @var array
     * массив со всеми тэгами IMG
     */
    protected $imgContent = array(
        
    );

    /**
     * @var string
     */
    protected $pageContent = '';

    public function __construct(PreParser $preParser, Parser $parser)
    {
        $this->preParser = $preParser;
        $this->parser    = $parser;
    }

    public function indexAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest()) {
            $url = $request->getPost('url');

            $data = $this->parser->parse($url);

            return new JsonModel([
                'data' => $data
            ]);
        }

        return new ViewModel();
    }

    public function preParseAction()
    {
        $request = $this->getRequest();

        if ($request->isXmlHttpRequest()) {
            $url = $request->getPost('url');

            $this->pageContent = $this->preParser->getStructure($url);

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
                            $currentText = trim(preg_replace('/\s{2,}/', ' ', substr(strrchr($currentLine, '>'), 1)));

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

                                $currentQuotes1 = null;
                                $currentQuotes2 = null;

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

            return new JsonModel([
                'textContent' => $this->textContent,
                'imgContent'  => $this->imgContent
            ]);
        }


        return new ViewModel();
    }
}