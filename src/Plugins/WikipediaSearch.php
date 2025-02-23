<?php
namespace LaravelNeuro\Plugins;

use LaravelNeuro\Plugins\Plugin;
use LaravelNeuro\Pipeline;
use LaravelNeuro\Prompts\SUAprompt;

use Mis3085\Tiktoken\Facades\Tiktoken;

use RoachPHP\Roach;
use RoachPHP\Spider\Configuration\Overrides;
use LaravelNeuro\Plugins\Resources\SpiderWikipediaSearch;

class WikipediaSearch extends Plugin {

    function __construct(Pipeline $pipeline)
    {
        $this->agent = $pipeline;
        
    }

    function query(string $query, array $options)
    {

        $defaults = [
            "articleTokenLimit" => 0, 
            "lang" => "en", 
        ];

        $articleTokenLimit = $options["articleTokenLimit"] ?? $defaults["articleTokenLimit"];
        $lang = $options["lang"] ?? $defaults["lang"];

        $agent = $this->agent;
        
        $agent->setPrompt((new SUAprompt)
                                    ->pushSystem("You are a plugin component, specialized in generating search terms to find relevant Wikipedia articles. Your response to any prompt is always 'Try searching for: [keyword(s)]' where [keyword(s)] are one or more keywords for a single search query on Wikipedia. If the user specifies a language using a language code, make sure the [keyword(s)] you return are written for that language instance of Wikipedia. Keep your [keyword(s)] as small as possible and try to reduce it to the topic the user is actually attempting to learn about.")
                                    ->pushUser("I want to know more about \"The Laravel PHP Framework\". Language: en")
                                    ->pushAgent("Try searching for: Laravel")
                                    ->pushUser("I want to know more about \"$query\". Language: $lang"));

        $makeQuery = explode("Try searching for: ",$this->agent->responseOnly())[1];

        echo "Searching Wikipedia for: " . $makeQuery . "\n";

        $wikiBase = "https://$lang.wikipedia.org";
        $getArticle = Roach::collectSpider(
            SpiderWikipediaSearch::class,
              new Overrides(startUrls: [
                $wikiBase."/w/index.php?search=".$makeQuery]),
          );

        $response = (object) $getArticle[0]->all();

        $tokens = Tiktoken::count(implode("\n", $getArticle[0]->all()));
        if ($articleTokenLimit > 0 && $tokens > $articleTokenLimit)
        {
            echo "Article length of $tokens tokens exceeds token limit of $articleTokenLimit. Truncating...";
            $metaTokens = Tiktoken::count($response->title ."\n\n". $response->infobox ."\n\n". $response->preface ."\n\n");
            if ($metaTokens > $articleTokenLimit)
            {
                $response->preface = Tiktoken::limit($response->preface, ($articleTokenLimit - Tiktoken::count($response->title ."\n\n". $response->infobox. "..." ."\n\n"))) . "...";
                $response->article = "";
            }

            $response->article = Tiktoken::limit($response->article, ($articleTokenLimit - Tiktoken::count($response->title ."\n\n". $response->infobox ."\n\n". $response->preface. "..." ."\n\n"))) . "...";
        }
        return $response;
    }

}