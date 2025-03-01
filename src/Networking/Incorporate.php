<?php
namespace LaravelNeuro\Networking;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use LaravelNeuro\Networking\Database\Models\NetworkCorporation;
use LaravelNeuro\Networking\Unit;

use LaravelNeuro\Enums\TransitionType;
use LaravelNeuro\Enums\IncorporatePrebuild;

/**
 * Handles the setup and installation process for a Laravel Neuro Corporation.
 * 
 * Incorporate is responsible for validating and processing a corporation's setup
 * configuration (typically provided via a JSON setup file) and installing it by:
 * - Creating the necessary directory structure and files.
 * - Creating corresponding database models and migrations.
 * - Registering units, transitions, and models defined in the setup.
 *
 * @package LaravelNeuro
 */
class Incorporate {

    /**
     * The name of the corporation.
     *
     * @var string
     */
    protected string $name;

    /**
     * The namespace for the corporation.
     *
     * @var string
     */
    protected string $nameSpace;

    /**
     * A short description of the corporation.
     *
     * @var string
     */
    protected string $description;  

    /**
     * The charta or foundational document for the corporation.
     *
     * @var string
     */
    protected string $charta;

    /**
     * A collection of errors encountered during setup.
     *
     * @var Collection
     */
    protected Collection $errors;

    /**
     * A collection of Unit objects associated with the corporation.
     *
     * @var Collection
     */
    protected Collection $units;

    /**
     * A collection of transition definitions for the corporation.
     *
     * @var Collection
     */
    protected Collection $transitions;

    /**
     * A collection of additional model configurations.
     *
     * @var Collection
     */
    protected Collection $models;

    /**
     * Incorporate constructor.
     *
     * Initializes empty collections for units, transitions, models, and errors.
     */
    function __construct()
    {
        $this->units = collect([]);
        $this->transitions = collect([]);
        $this->models = collect([]);
        $this->errors = collect([]);
    }

    /**
     * Validates that a property exists in an object and is of the expected type.
     *
     * For enum types, it validates that the property value matches one of the enum cases,
     * and converts the value to the enum instance.
     *
     * @param object $object The object containing the property.
     * @param string $propertyName The name of the property to validate.
     * @param string $expectedType The expected type (or enum name) of the property.
     * @return mixed The validated property value.
     * @throws \Exception if the property is missing or not of the expected type.
     */
    private static function validateProperty($object, $propertyName, $expectedType)
    {
        if (!isset($object->$propertyName)) {
            throw new \Exception("Property '{$propertyName}' is missing.");
        }
        // If expected type is an enum defined under LaravelNeuro\Enums
        if (enum_exists('LaravelNeuro\\Enums\\' . $expectedType)) {
            $isValidEnumValue = false;
            $enum = 'LaravelNeuro\\Enums\\' . $expectedType;
            foreach ($enum::cases() as $case) {
                if ($object->$propertyName === $case->value) {
                    $isValidEnumValue = true;
                    $object->$propertyName = $case;
                    break;
                }
            }
            if (!$isValidEnumValue) {
                throw new \Exception("Property '{$propertyName}' value of '{$object->$propertyName}' does not match any case of enum '{$expectedType}'.");
            }
        } elseif (gettype($object->$propertyName) !== $expectedType && !($object->$propertyName instanceof $expectedType)) {
            throw new \Exception("Property '{$propertyName}' is not of expected type '{$expectedType}'.");
        }
        return $object->$propertyName;
    }

    /**
     * Sets the name of the corporation.
     *
     * @param string $set The corporation's name.
     * @return self
     */
    public function setName(string $set)
    {
        $this->name = $set;
        return $this;
    }

    /**
     * Sets the namespace for the corporation.
     *
     * @param string $set The namespace.
     * @return self
     */
    public function setNameSpace(string $set)
    {
        $this->nameSpace = $set;
        return $this;
    }

    /**
     * Sets the description for the corporation.
     *
     * @param string $set The description.
     * @return self
     */
    public function setDescription(string $set)
    {
        $this->description = $set;
        return $this;    
    }

    /**
     * Sets the charta for the corporation.
     *
     * @param string $set The charta.
     * @return self
     */
    public function setCharta(string $set)
    {
        $this->charta = $set;
        return $this;    
    }

    /**
     * Retrieves the corporation's name.
     *
     * @return string The name of the corporation.
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Retrieves the corporation's namespace.
     *
     * @return string The namespace.
     */
    public function getNameSpace()
    {
        return $this->nameSpace;
    }

    /**
     * Retrieves the corporation's description.
     *
     * @return string The description.
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Retrieves the corporation's charta.
     *
     * @return string The charta.
     */
    public function getCharta()
    {
        return $this->charta;
    }

    /**
     * Adds a Unit instance to the corporation.
     *
     * @param Unit $unit The Unit to add.
     * @return self
     */
    public function pushUnit(Unit $unit)
    {
        $this->units->push($unit); 
        return $this;  
    }

    /**
     * Adds a model configuration to the corporation.
     *
     * @param mixed $model The model configuration.
     * @return self
     */
    public function pushModel($model)
    {
        $this->models->push($model); 
        return $this;  
    }

    /**
     * Adds a transition definition to the corporation.
     *
     * @param mixed $transition The transition definition.
     * @return self
     */
    public function pushTransition($transition)
    {
        $this->transitions->push($transition); 
        return $this;  
    }

    /**
     * Prebuilds the corporation setup file based on the given namespace and type.
     *
     * Normalizes the provided namespace, selects the appropriate stub (JSON or PHP),
     * and writes the setup file to the designated storage disk.
     *
     * @param string $nameSpace The desired namespace for the corporation.
     * @param IncorporatePrebuild $type The type of setup file to create (JSON or PHP).
     * @return bool True if the file was successfully created; false otherwise.
     * @throws \InvalidArgumentException if the namespace does not start with "App\\" when required.
     */
    public static function prebuild(string $nameSpace, IncorporatePrebuild $type = IncorporatePrebuild::JSON) : bool
    {
        $nameSpace = Str::replace(' ', '', $nameSpace);
        $camelize = explode(' ', $nameSpace);
        if(count($camelize) > 1)
        {
            foreach($camelize as $key => $word)
            {
                $camelize[$key] = ucfirst($word);
            }
        }
        $nameSpace = implode('', $camelize);
        
        // Normalize and validate the namespace
        $nameSpace = preg_replace('/[^a-zA-Z0-9\/_\\\\]/', '_', $nameSpace);
        if(Str::contains($nameSpace, '\\'))
        {
            $destination = Str::replace('\\', '/', $nameSpace); // Convert backslashes to forward slashes  
            if (!Str::startsWith($destination, 'App/')) {
                throw new \InvalidArgumentException("Any fully qualified Namespace must start with 'App\\'. If you input just a name, the default Namespace of App\\Corporations will be used.");
            }
            $destination = Str::after($destination, 'App/'); // Remove 'App/' prefix
        }
        else
        {
            $destination = config('laravelneuro.default_namespace', 'Corporations') . '/' . $nameSpace;
        }

        switch ($type) {
            case IncorporatePrebuild::JSON:
                $setup = Incorporate::getStub('setup.json');
                $setupFileName = 'setup.json';
                break;
            case IncorporatePrebuild::PHP:
                $setup = Incorporate::getStub('setup.php');
                $setupFileName = 'setup.php';
                break;
            default:
                return false;
        }
        $setup = str_replace('{{CorporationNamespace}}', $nameSpace, $setup);

        $filePath = $destination . '/' . $setupFileName;

        // Check and create directory
        if (!Storage::disk('lneuro_app')->exists($destination)) {
            Storage::disk('lneuro_app')->makeDirectory($destination);
        }

        // Check if file exists and write content
        if (!Storage::disk('lneuro_app')->exists($filePath)) {
            Storage::disk('lneuro_app')->put($filePath, $setup);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Installs the corporation based on a JSON setup.
     *
     * Parses the provided JSON, validates required properties for the corporation,
     * units, transitions, and models, and creates the necessary database models and
     * files via Artisan commands. Returns an array with installation details.
     *
     * @param mixed $json The JSON string containing the corporation setup.
     * @return mixed An array containing corporation ID, name, description, units, transitions, and errors.
     * @throws \Exception if the JSON is invalid.
     */
    public static function installFromJSON($json = false)
    {

            $import = json_decode($json);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Invalid JSON: " . json_last_error_msg());
            }

            $setup = new Incorporate;

            $setup->setName(self::validateProperty($import, "name", "string"))
                  ->setNameSpace(self::validateProperty($import, "nameSpace", "string"))
                  ->setDescription(self::validateProperty($import, "description", "string"))
                  ->setCharta(self::validateProperty($import, "charta", "string"));
            
            foreach(self::validateProperty($import, "units", "array") as $unit)
            {
                $constructUnit = new Unit;
                $constructUnit->setName(self::validateProperty($unit, "name", "string"))
                              ->setDescription(self::validateProperty($unit, "description", "string"));

                $defaultReceiver = self::validateProperty($unit, "defaultReceiver", "object");
                    $defaultReceiverType = self::validateProperty($defaultReceiver, "type", "UnitReceiver");
                    $defaultReceiverName = self::validateProperty($defaultReceiver, "name", "string");

                $constructUnit->setDefaultReceiver($defaultReceiverType, $defaultReceiverName);
                
                if(isset($unit->dataSets))
                {
                    foreach(self::validateProperty($unit, "dataSets", "array") as $dataSet)
                    {
                        $constructUnit->configureDataSet(
                            self::validateProperty($dataSet, "name", "string"), 
                            self::validateProperty($dataSet, "completion", "string"), 
                            self::validateProperty($dataSet, "structure", "object")
                        );
                    }
                }

                foreach(self::validateProperty($unit, "agents", "array") as $agent)
                {
                    $constructAgent = new Agent;
                    $constructAgent->setName(self::validateProperty($agent, "name", "string"));
                    $constructAgent->setPipelineClass(self::validateProperty($agent, "pipeline", "string"));

                    if(isset($agent->model)) $constructAgent->setModel(self::validateProperty($agent, "model", "string"));
                    if(isset($agent->apiLink)) $constructAgent->setApi(self::validateProperty($agent, "apiLink", "string"));                
                    
                    if(!empty($agent->role ?? null)) $constructAgent->setRole(self::validateProperty($agent, "role", "string"));
                    if(!empty($agent->prompt ?? null)) $constructAgent->setPrompt(self::validateProperty($agent, "prompt", "string"));
                    if(!empty($agent->promptClass ?? null)) $constructAgent->setPromptClass(self::validateProperty($agent, "promptClass", "string"));
                    
                    if(isset($agent->apiType)) $constructAgent->setApiType(self::validateProperty($agent, "apiType", "APItype"));
                    if(isset($agent->outputModel)) $constructAgent->setOutputModel(self::validateProperty($agent, "outputModel", "string"));
                    if(isset($agent->validateOutput)) $constructAgent->validateOutput(self::validateProperty($agent, "validateOutput", "boolean"));

                    $constructUnit->pushAgent($constructAgent);                                  
                }

                $setup->pushUnit($constructUnit);
            }

            if(isset($import->models) && is_array(self::validateProperty($import, "models", "array")) && count($import->models) !== 0)
            {
                foreach($import->models as $model)
                {              
                    self::validateProperty($model, "name", "string");
                    if(isset($model->migration)) self::validateProperty($model, "migration", "boolean");
                    else $model->migration = false;
                    $setup->pushModel($model);
                }
            }

            foreach(self::validateProperty($import, "transitions", "array") as $transition)
            {              
                self::validateProperty($transition, "type", "TransitionType");
                self::validateProperty($transition, "transitionName", "string");
                self::validateProperty($transition, "transitionHandle", "string");
                $setup->pushTransition($transition);
            }

            return $setup->install();

    }

    /**
     * Installs the corporation by creating a NetworkCorporation record and generating necessary files.
     *
     * Creates a new NetworkCorporation record with the corporation's name, namespace,
     * description, and charta. Then, it creates the required directories, models, migrations,
     * and transition files based on the setup information. Returns an array of installation details.
     *
     * @return array An array containing the corporation ID, name, description, units, transitions, and any errors.
     */
    public function install()
    {
            $corporation = new NetworkCorporation;
            $corporation->name = $this->name;
            $corporation->nameSpace = $this->getNameSpace();
            $corporation->description = $this->description;
            $corporation->charta = $this->charta;

            $corporation->save();

            $nameSpace = $this->getNameSpace();

            if(Str::contains($nameSpace, '\\'))
            {
                $destination = Str::replace('\\', '/', $nameSpace); // Convert backslashes to forward slashes  
                if (!Str::startsWith($destination, 'App/')) {
                    throw new \InvalidArgumentException("Any fully qualified Namespace must start with 'App\\'. If you input just a name, the default Namespace of App\\Corporations will be used.");
                }
                $destination = Str::after($destination, 'App/'); // Remove 'App/' prefix
            }
            else
            {
                $destination = config('laravelneuro.default_namespace', 'Corporations') . '/' . $nameSpace;
                $nameSpace = 'App'.'\\'.config('laravelneuro.default_namespace', 'Corporations').'\\'.$nameSpace;
            }

            $mainscriptName = explode('\\',$nameSpace);
            $mainscriptName = $mainscriptName[count($mainscriptName) - 1];

            if (!Storage::disk('lneuro_app')->exists($destination)) {
                Storage::disk('lneuro_app')->makeDirectory($destination);
            }
            if (!Storage::disk('lneuro_app')->exists($destination . '/Database/migrations')) {
                Storage::disk('lneuro_app')->makeDirectory($destination . '/Database/migrations');
            }
            if (!Storage::disk('lneuro_app')->exists($destination . '/Database/Models')) {
                Storage::disk('lneuro_app')->makeDirectory($destination . '/Database/Models');
            }

            $migrationNumber = 0;

                foreach($this->models as $key => $model)
                {     
                    $makeModel = $nameSpace.'\\'.'Database'.'\\'.'Models'.'\\'.$model->name;

                    $commandState = Command::SUCCESS;

                    try {
                    $commandState = Artisan::call('make:model',  ["name" => $makeModel]);
                    if($commandState == Command::FAILURE) 
                                {
                                    $this->errors->push(sprintf('There was a problem creating your Laravel Neuro Corporation\'s Model with the name [%s].', $model->name));
                                }
                    }
                    catch(\Exception $e)
                    {
                        $this->errors->push($e);
                    }

                    try {
                        if($model->migration)
                        {
                            $migrationNumber++;
                            $migrationName = str_pad($migrationNumber, 3, "0", STR_PAD_LEFT) . $model->name . '_migration';
                            $snakeCaseName = Str::snake($model->name);
                            $tableName = Str::plural($snakeCaseName);
                            $migrationPath = app_path($destination.'/Database/migrations');
                            
                            $commandState = Artisan::call('lneuro:make-network-migration', [
                                                                "name" => $migrationName, 
                                                                "--path" => $migrationPath,
                                                                "--create" => $tableName,
                                                                "--truename"
                                                                ]);
                            if($commandState == Command::FAILURE) 
                                {
                                    $this->errors->push(sprintf('There was a problem creating your Laravel Neuro Corporation\'s migration for the Model with the name [%s], it may already exist.', $model->name));
                                }
                        }
                    }
                    catch(\Exception $e)
                    {
                        $this->errors->push($e);
                    }
                }

            $units = [];
            foreach($this->units as $unit)
            {
                $unitRef = $unit->install($corporation->id);
                $units[] = $unitRef;
            }

            $transitionConstants = '';

            foreach($this->transitions as $transition)
            {
                $dirPath = $destination.'/Transitions';

                if (!Storage::disk('lneuro_app')->exists($dirPath)) {
                    Storage::disk('lneuro_app')->makeDirectory($dirPath);
                }

                $transition->transitionName = ucwords($transition->transitionName);
                $transition->transitionName = str_replace(' ', '', $transition->transitionName);
                $transition->transitionName = preg_replace('/[^a-zA-Z0-9_]/', '_', $transition->transitionName);

                $filePath = $dirPath.'/'.$transition->transitionName.'.php';
                if (!Storage::disk('lneuro_app')->exists($filePath)) {
                    switch($transition->type)
                    {
                        case TransitionType::AGENT :
                            $transitionStub = Incorporate::getStub('Transitions/transition.agent');
                            $unitAgent = explode(".", $transition->transitionHandle);
                            $transitionStub = str_replace('{{AgentName}}', $unitAgent[1], $transitionStub);
                            foreach($units as $unit)
                            {
                                if($unit["unitName"] == $unitAgent[0])
                                {
                                    foreach($unit["agents"] as $agent)
                                    {
                                        if($agent["agentName"] == $unitAgent[1])
                                        {
                                            $agentId = $agent["agentId"];
                                        }
                                    }
                                }
                            }
                            $configConst = "TRANSITION_".strtoupper($unitAgent[1])."_AGENT";
                            $transitionStub = str_replace('{{AgentId}}', 'Config::'.$configConst, $transitionStub);
                            if(!str_contains($transitionConstants, "const $configConst"))
                                $transitionConstants .= "\tconst $configConst = $agentId;\n";
                            break;

                        case TransitionType::UNIT :
                            $transitionStub = Incorporate::getStub('Transitions/transition.unit');
                            $transitionStub = str_replace('{{UnitName}}', $transition->transitionHandle, $transitionStub);
                            foreach($units as $unit)
                            {
                                if($unit["unitName"] == $transition->transitionHandle)
                                {
                                    $unitId = $unit["unitId"];
                                }
                            }
                            $configConst = "TRANSITION_".strtoupper($transition->transitionHandle)."_UNIT";
                            $transitionStub = str_replace('{{UnitId}}', 'Config::'.$configConst, $transitionStub);
                            if(!str_contains($transitionConstants, "const $configConst"))
                                $transitionConstants .= "\tconst $configConst = $unitId;\n";
                            break;

                        case TransitionType::FUNCTION :
                            $transitionStub = Incorporate::getStub('Transitions/transition.function');
                            break;

                        default:
                            throw new \Exception("One of your Transitions has an invalid type.");
                    }

                    $transitionStub = str_replace('{{TransitionName}}', $transition->transitionName, $transitionStub);
                    $transitionStub = str_replace('{{CorporationNameSpace}}', $nameSpace, $transitionStub);
                    $transitionStub = str_replace('{{CorporationName}}', $mainscriptName, $transitionStub);

                    Storage::disk('lneuro_app')->put($filePath, $transitionStub);
                } 
                else
                {
                    switch($transition->type)
                    {
                    case TransitionType::AGENT :
                        $unitAgent = explode(".", $transition->transitionHandle);
                        foreach($units as $unit)
                        {
                            if($unit["unitName"] == $unitAgent[0])
                            {
                                foreach($unit["agents"] as $agent)
                                {
                                    if($agent["agentName"] == $unitAgent[1])
                                    {
                                        $agentId = $agent["agentId"];
                                    }
                                }
                            }
                        }
                        $configConst = "TRANSITION_".strtoupper($unitAgent[1])."_AGENT";
                        if(!str_contains($transitionConstants, "const $configConst"))
                            $transitionConstants .= "\tconst $configConst = $agentId;\n";
                        
                        break;

                    case TransitionType::UNIT :
                        foreach($units as $unit)
                        {
                            if($unit["unitName"] == $transition->transitionHandle)
                            {
                                $unitId = $unit["unitId"];
                            }
                        }
                        $configConst = "TRANSITION_".strtoupper($transition->transitionHandle)."_UNIT";
                        if(!str_contains($transitionConstants, "const $configConst"))
                            $transitionConstants .= "\tconst $configConst = $unitId;\n";
                        break;
                    }
                }
            }

            if (!Storage::disk('lneuro_app')->exists($destination)) {
                Storage::disk('lneuro_app')->makeDirectory($destination);
            }

            $transitionNamespace = $nameSpace.'\\Transitions';
            $transitionCount = $this->transitions->count();

            $filePath = $destination.'/'.$mainscriptName.'.php';
            if (!Storage::disk('lneuro_app')->exists($filePath)) {

                $autoloadTransitions = '';
                $autoloadBasicTransition = false;
                $corporationFile = Incorporate::getStub('corporation');

                if($transitionCount > 0)
                {
                    $initial = Incorporate::getStub('corporation.initial');
                    $transitionName = $this->transitions->first()->transitionName;
                    $transitionName = ucwords($transitionName);
                    $transitionName = str_replace(' ', '', $transitionName);
                    $transitionName = preg_replace('/[^a-zA-Z0-9_]/', '_', $transitionName);

                    $autoloadTransitions .= "use $transitionNamespace\\$transitionName;\n";

                    $initial = str_replace('{{FirstTransition}}', $transitionName, $initial);
                    $corporationFile = str_replace('{{initial}}', $initial, $corporationFile);

                    if($transitionCount > 1)
                    {
                        $final = Incorporate::getStub('corporation.final');
                        $transitionName = $this->transitions->last()->transitionName;
                        $transitionName = ucwords($transitionName);
                        $transitionName = str_replace(' ', '', $transitionName);
                        $transitionName = preg_replace('/[^a-zA-Z0-9_]/', '_', $transitionName);

                        $autoloadTransitions .= "use $transitionNamespace\\$transitionName;\n";

                        $final = str_replace('{{FinalTransition}}', $transitionName, $final);
                        $corporationFile = str_replace('{{final}}', $final, $corporationFile);
                    }
                    else
                    {
                        $final = Incorporate::getStub('corporation.final');
                        $final = str_replace('{{FinalTransition}}', 'Transition', $final);
                        $corporationFile = str_replace('{{final}}', $final, $corporationFile);
                        $autoloadBasicTransition = true;
                    }

                    if($transitionCount > 2)
                    {
                        $continue = Incorporate::getStub('corporation.continue');
                        $intermediateTransitions = 'switch($this->getHeadPosition()) {'."\n";

                        foreach($this->transitions as $key => $transition)
                        {
                            if($key == 0) continue;
                            if($key == ($transitionCount - 1)) continue;
                            
                            $transitionName = $transition->transitionName;
                            $transitionName = ucwords($transitionName);
                            $transitionName = str_replace(' ', '', $transitionName);
                            $transitionName = preg_replace('/[^a-zA-Z0-9_]/', '_', $transitionName);

                            if(!str_contains($autoloadTransitions, "use $transitionNamespace\\$transitionName;"))
                                $autoloadTransitions .= "use $transitionNamespace\\$transitionName;\n";

                            $intermediateTransitions .= "\t\t\tcase ".$key.":\n";
                            $intermediateTransitions .= "\t\t\t\t".'$transition = new '.$transitionName.'($this->project->id, $head, $this->models);'."\n";
                            $intermediateTransitions .= "\t\t\t\tbreak;"."\n";
                        }

                        $intermediateTransitions .= "\t\t\tdefault:"."\n";
                        $intermediateTransitions .= "\t\t\t\t".'$transition = new Transition($this->project->id, $head, $this->models);'."\n";
                        $intermediateTransitions .= "\t\t\t\tbreak;"."\n\t\t}";
                        $autoloadBasicTransition = true;

                        $continue = str_replace('{{IntermediateTransitions}}', $intermediateTransitions, $continue);
                        $corporationFile = str_replace('{{continue}}', $continue, $corporationFile);
                    }
                    else
                    {
                        $continue = Incorporate::getStub('corporation.continue');
                        $continue = str_replace('{{IntermediateTransitions}}', '$transition = new Transition($this->project->id, $head, $this->models);', $continue);
                        $corporationFile = str_replace('{{continue}}', $continue, $corporationFile);
                        $autoloadBasicTransition = true;
                    }
                }
                else
                {
                    $initial = Incorporate::getStub('corporation.initial');
                    $initial = str_replace('{{InitialTransition}}', 'Transition', $initial);
                    $continue = Incorporate::getStub('corporation.continue');
                    $continue = str_replace('{{IntermediateTransitions}}', '$transition = new Transition($this->project->id, $head, $this->models);', $continue);
                    $final = Incorporate::getStub('corporation.final');
                    $final = str_replace('{{FinalTransition}}', 'Transition', $final);
                    $autoloadBasicTransition = true;
                    $corporationFile = str_replace('{{initial}}', $initial, $corporationFile);
                    $corporationFile = str_replace('{{continue}}', $continue, $corporationFile);
                    $corporationFile = str_replace('{{final}}', $final, $corporationFile);
                }

                if($autoloadBasicTransition)
                {
                    $autoloadTransitions .= "use LaravelNeuro\\Networking\\Transition;\n";
                }

                $corporationFile = str_replace('{{AutoloadTransitions}}', $autoloadTransitions, $corporationFile);
                $corporationFile = str_replace('{{CorporationNameSpace}}', $nameSpace, $corporationFile);
                $corporationFile = str_replace('{{CorporationName}}', $mainscriptName, $corporationFile);
                Storage::disk('lneuro_app')->put($filePath, $corporationFile);
            }

            $filePath = $destination.'/Config.php';
            $config = Incorporate::getStub('config');
            $config = str_replace('{{CorporationNameSpace}}', $nameSpace, $config);
            $config = str_replace('{{CorporationName}}', $mainscriptName, $config);
            $config = str_replace('{{ConstCorporation}}', $corporation->id, $config);
            $config = str_replace('{{ConstStates}}', $transitionCount, $config);   
            $config = str_replace('{{TransitionConstants}}', $transitionConstants, $config);  
            
            Storage::disk('lneuro_app')->put($filePath, $config);

                $filePath = $destination.'/Bootstrap.php';

                if (!Storage::disk('lneuro_app')->exists($filePath)) {      
                $bootstrap = Incorporate::getStub('bootstrap');
                $bootstrap = str_replace('{{CorporationNameSpace}}', $nameSpace, $bootstrap);
                $bootstrap = str_replace('{{CorporationName}}', $mainscriptName, $bootstrap);
                $bootstrap = str_replace('{{CorporationNameSpaceDoubleSlash}}', str_replace('\\','\\\\', $nameSpace), $bootstrap);
                
                Storage::disk('lneuro_app')->put($filePath, $bootstrap);
                }

            return ["corporationId" => $corporation->id, "corporationName" => $corporation->name, "corporationDescription" => $corporation->description, "units" => $units, "transitions" => $this->transitions->toArray(), "errors" => $this->errors->toArray()];
    }
    
    /**
     * Retrieves the content of a stub file for the corporation.
     *
     * Looks for a stub file in the /resources/stubs/Corporation/ directory relative to the current directory.
     *
     * @param string $stub The name of the stub file (without the .stub extension).
     * @return string The contents of the stub file.
     */
    public static function getStub(string $stub)
    {
        return file_get_contents(__DIR__."/../resources/stubs/Corporation/$stub.stub");
    }

}