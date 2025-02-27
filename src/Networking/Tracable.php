<?php

namespace LaravelNeuro\Networking;

use LaravelNeuro\Networking\Database\Models\NetworkHistory;
use LaravelNeuro\Enums\TuringHistory;

/**
 * Defines debug and help methods for use by Corporation and Transition class instances to log to the console
 * and save NetworkHistory entries regarding the current Corporation lifecycle.
 * 
 * @package LaravelNeuro
 */
trait Tracable {

    /**
     * Flag indicating whether history entries should be saved to the database.
     *
     * @var bool
     */
    public bool $saveHistory = true;

    /**
     * Flag indicating whether debugging output is enabled (posts history to console among other information).
     *
     * @var bool
     */
    public bool $debug = false;
    
    /**
     * Outputs debug information if debugging is enabled.
     *
     * @param string $info The debug message.
     * @return void
     */
    public function debug(string $info)
    {
        if ($this->debug) {
            $dateObj = \DateTime::createFromFormat('0.u00 U', microtime());
            echo '[' . $dateObj->format('H:i:s.u') . ']: ' . $info . "\n";
        }
    }

    /**
     * Saves history entries to the database.
     *
     * @param string $info The debug message.
     * @return void
     */
    public function history(TuringHistory $entryType, $content) 
    {
        if($this->saveHistory)
        {
            $history = NetworkHistory::create([
                'entryType' => $entryType, 
                'project_id' => $this->project->id, 
                'content' => $content
                ]);
            return $history;
        }
        return null;
    }
}