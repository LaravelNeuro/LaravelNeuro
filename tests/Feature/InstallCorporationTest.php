<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Foundation\Testing\WithConsoleEvents;

use Tests\PackageTestCase;
use Tests\Helpers\ApiSimulator;

class InstallCorporationTest extends PackageTestCase {

    use RefreshDatabase;
    use WithConsoleEvents;

    protected function tearDown(): void
    {
        parent::tearDown();
        if (File::exists(app_path('Corporations'))) {
            File::deleteDirectory(app_path('Corporations'));
        }
    }

    public function testPrebuild(): void
    {
      $this->artisan('lneuro:prebuild', ['name' => 'TestCorporation'])
           ->expectsOutput("Your setup file has successfully been created in app/Corporations/TestCorporation.")
           ->expectsOutput("Once you have filled it out, you can install your Corporation using the artisan command")
           ->expectsOutput(" artisan lneuro:install TestCorporation  ")
           ->expectsOutput("then, you may run it using")
           ->expectsOutput(" artisan lneuro:run TestCorporation {task}  ")
           ->expectsOutput("or by programmatically calling the run() method on a new App\Corporations\TestCorporation object. Make sure to pass your task string as the first parameter to your new App\Corporations\TestCorporation's constructor.")
           ->assertSuccessful();

      $destination = app_path('Corporations/TestCorporation');

      $this->assertFileExists($destination, 'State Machine folder could not be created successfully.');
      $this->assertFileExists($destination . '/' . 'setup.json', 'State Machine installation script missing.');

      $this->assertTrue(json_validate(file_get_contents($destination . '/' . 'setup.json', 'State Machine installation script not valid json.')));
    }

    public function testInstall(): void
    {
      $this->artisan('lneuro:prebuild', ['name' => 'TestCorporation'])
           ->expectsOutput("Your setup file has successfully been created in app/Corporations/TestCorporation.")
           ->expectsOutput("Once you have filled it out, you can install your Corporation using the artisan command")
           ->expectsOutput(" artisan lneuro:install TestCorporation  ")
           ->expectsOutput("then, you may run it using")
           ->expectsOutput(" artisan lneuro:run TestCorporation {task}  ")
           ->expectsOutput("or by programmatically calling the run() method on a new App\Corporations\TestCorporation object. Make sure to pass your task string as the first parameter to your new App\Corporations\TestCorporation's constructor.")
           ->assertSuccessful();

        $source = __DIR__ . '/../resources/TestCorporation/setup.json';
        $destination = app_path('Corporations/TestCorporation');

        File::delete($destination . '/' . 'setup.json');
        File::copy($source, $destination . '/' . 'setup.json');

        $this->assertFileExists($destination, 'State Machine folder could not be created successfully.');
        $this->assertFileExists($destination . '/' . 'setup.json', 'State Machine installation script missing.');

        $this->artisan('lneuro:install', ['namespace' => 'TestCorporation', '--consolidate' => true])
             ->expectsOutput('Laravel Neuro state-machine migration created successfully.')
             ->expectsOutput('Your Corporation has been installed successfully.')
             ->assertSuccessful();

        $this->assertFileExists($destination . '/' . 'TestCorporation.php');
        $this->assertFileExists($destination . '/' . 'Config.php');
        $this->assertFileExists($destination . '/' . 'Bootstrap.php');

        $this->assertFileExists($destination . '/' . 'Database');
        $this->assertFileExists($destination . '/' . 'Database' . '/' . 'migrations');
        $this->assertFileExists($destination . '/' . 'Database' . '/' . 'Models');
        $this->assertFileExists($destination . '/' . 'Database' . '/' . 'Models' . '/' . 'TestModel.php');
        $this->assertFileExists($destination . '/' . 'Transitions');
        $this->assertFileExists($destination . '/' . 'Transitions' . '/' . 'AudioTTStest.php');
        $this->assertFileExists($destination . '/' . 'Transitions' . '/' . 'ChatCompletionTest.php');
        $this->assertFileExists($destination . '/' . 'Transitions' . '/' . 'ImageGenerationTest.php');
    }

}