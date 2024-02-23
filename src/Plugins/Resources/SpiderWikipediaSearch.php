<?php

namespace LaravelNeuro\LaravelNeuro\Plugins\Resources;

use Generator;
use RoachPHP\Http\Response;
use RoachPHP\Spider\BasicSpider;
use RoachPHP\Spider\ParseResult;
use Symfony\Component\DomCrawler\Crawler;

class SpiderWikipediaSearch extends BasicSpider
{

    public int $concurrency = 1;

    public int $requestDelay = 1;

    /**
     * @return Generator<ParseResult>
     */
    public function parse(Response $response): Generator
    {
        $heading = $response->filter("header h1, h1#firstHeading")->text("No Results");
        echo "page heading: " . $heading . "\n";
        if ($response->filter('[role="main"] form#search')->count() > 0)
        {
            $goto = explode("/w/", $response->getRequest()->getUri())[0] . $response->filter("li.mw-search-result a")->first()->attr("href");
            yield $this->request('GET', 
            $goto);
        }
        elseif($heading == "No Results")
        {
            yield $this->item(["error" => "no articles found."]);
        }
        else
        {
            $infobox = $response->filter(".infobox tr")->each(function($e){
                $e->filter('td style, td script')->each(function (Crawler $crawler) {
                    foreach ($crawler as $node) {
                        $node->parentNode->removeChild($node);
                    }
                });
                if($e->filter('td')->count() > 1)
                {
                    return $e->filter("td:nth-child(1)")->text("") . ": " . $e->filter("td:nth-child(2)")->text("");
                }
                else
                {
                    return $e->filter("th")->text("") . ": " . $e->filter("td")->text("");   
                }
                
            });

            $delimiter = "/wikispider/break/wikispider/";

            $article = $response->filter('[role="main"] p, [role="main"] h2, [role="main"] h3, [role="main"] h4, [role="main"] table:not(.infobox):not(.sidebar), [role="main"] meta[property="mw:PageProp/toc"], [role="main"] [role="navigation"]')->each(function($e) use ($delimiter){
                if ($e->attr('property') == 'mw:PageProp/toc' || $e->attr('role') == 'navigation') {
                    return $delimiter;
                }
                $e->filter('style, script, .mw-editsection')->each(function (Crawler $crawler) {
                    foreach ($crawler as $node) {
                        $node->parentNode->removeChild($node);
                    }
                });

                switch($e->nodeName())
                {
                    case "h2":
                        return "## ".$e->text("");
                        break;
                    case "h3":
                        return "### ".$e->text("");
                        break;
                    case "h4":
                        return "#### ".$e->text("");
                        break;
                    case "table":
                        return "[Table Start]" . $e->text("") . "[/Table End]";
                        break;
                    default:
                        return $e->text("");
                        break;
                }
                
            });

            $divvy = explode($delimiter, implode("\n", $article));

            $preface = $divvy[0];
            $article = $divvy[1];

            yield $this->item(["title" => $heading, "infobox" => implode("\n", preg_replace('/\[[0-9]+\]/', '',$infobox)), "preface" => preg_replace('/\[[0-9]+\]/', '',$preface), "article" => preg_replace('/\[[0-9]+\]/', '',$article)]);
        }
    }
}
