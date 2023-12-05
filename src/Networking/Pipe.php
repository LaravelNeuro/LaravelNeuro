<?php
namespace Kbirenheide\LaravelNeuro\Networking;

use Illuminate\Support\Collection;

class Pipe extends Collection {

    public function setReceiverType(string $set)
    {
        $this->put("receiverType", $set);
        return $this;
    }

    public function setRetrieverType(string $set)
    {
        $this->put("retrieverType", $set);
        return $this;
    }

    public function setRetriever(string $set)
    {
        $this->put("retriever", $set);
        return $this;
    }
}