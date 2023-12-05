<?php

$install = new Incorporate();
$install->setName("Test Corporation")
        ->setDescription("This Corporation uses Agents to solve the input request.")
        ->setCharta("This charta explains, what types of user requests the Corporation specializes in.")
        ->setInitialReceiver("Front Desk") // For initialization The user issued task will be sent to this Unit
        ->pushUnit(
                    (new Unit)
                        ->setName("Front Desk")     
                        ->setDescription("Receives initial Prompt and decides whether the Corporation can fulfill it.")                  
                        ->configureDataSet("Contract" // DataSet name
                            , [
                                "proceed" => false,
                                "request" => "string",
                                "department" => "Delivery",
                                "status" => "DECLINED",
                                "contract" => "The Front Desk has received a customer contract. Create a set of project stages from start to finish, add sequential tasks to complete for each stage, and assign each task to one of our departments, which are:\nProject Management and Coding. Set the first task of the first stage to ACTIVE and modify the related activeStage and activeTask entries. \nThe following is the customer's request:\n"
                            ]) // this data object will be converted into JSON format and stored in the database. It belongs to this Unit and will be loaded using Eloquent whenever the Unit is instantiated
                        ->pushAgent(
                            (new Agent)
                                    ->setModel("Zephyr")
                                    ->setApi("API URL")
                                    ->setName("Receptionist")
                                    ->setRole("You are the friendly front desk employee of our virtual corporation. Our charta is the following:\n{{corporation.Charta}}\nYou evaluate whether requests comply with our corporation's charta and respond with a JSON object. If the request complies with the corporation's charta, set 'proceed' to true, 'request' to the user's request, 'department' to 'Project Management', 'status' to 'ACTIVE', and . If it does not, return the JSON object without modification.")
                                    ->setOutputModel("internal.Contract")
                                    ->validateOutput(true)
                                    ->setPipe( 
                                        (new Pipe)
                                        ->setReceiverType("Unit")
                                        ->setRetrieverType("fromDataSet")
                                        ->setRetriever("internal.Contract.department")
                                        )
                            )
                        ->setDefaultReceiver("Agent", "Receptionist")
                )
        ->pushUnit(
                    (new Unit)
                        ->setName("Project Management")
                        ->setDescription("Creates the Project, dividing it into stages and tasks, then starts the first task.")  
                        ->configureDataSet("Project" // DataSet name
                            , [
                            "projectName" => "string",
                            "activeStage" => "integer representing a key in the stages property",
                            "stages" => [
                                [
                                    "name" => "string",
                                    "description" => "string",
                                    "status" => "PENDING/ACTIVE/DONE",
                                    "requirements" => ["string describing a requirement"],
                                    "activeTask" => "integer representing a key in the tasks property",
                                    "tasks" => [
                                        "name" => "string",
                                        "description" => "string",
                                        "status" => "PENDING/ACTIVE/DONE",
                                        "department" => "name of the assigned department"
                                    ]
                                ], 
                            ],
                            ]) 
                        ->pushAgent(
                            (new Agent)->setModel("Zephyr")
                                ->setName("Project Architect")
                                ->setApi("API URL")
                                ->setRole("You are the Project Manager of our corporation.")
                                ->setOutputModel("internal.Project") // OutputModel will be parsed into a json object and appended as an output schema to the system instructions for the Agent's model. parameter specifies the name of the internal DataSet to be used. by replacing "internal" with a Unit name, a dataset from another Unit within the Corporation can be imported.
                                ->validateOutput(true) // validate output json against the set OutputModel
                                ->setPipe(["Unit", "fromDataSet", "internal.Project.stages[internal.Project.activeStage].tasks[stages[internal.Project.activeTask]].department"])
                            )
                        ->setDefaultReceiver("Agent", "Project Architect")
            )
            ->pushUnit(
                (new Unit)
                    ->setName("Delivery")  
                    ->setDescription("Returns the final output.")                       
                    ->pushCompletion(
                        ["fromDataSet", "external.FrontDesk.Contract.request"]
                        )
                    ->setDefaultReceiver("Completion")
            ); // pushes unit to internal laravel collection