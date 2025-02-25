<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

use Tests\PackageTestCase;
use Tests\Helpers\ApiSimulator;

use LaravelNeuro\Networking\Database\Models\NetworkProject;
use LaravelNeuro\Networking\Database\Models\NetworkHistory;
use LaravelNeuro\Networking\Database\Models\NetworkState;

class RunCorporationTest extends PackageTestCase {

    use RefreshDatabase;

    protected function tearDown(): void
    {
        parent::tearDown();
        if (File::exists(app_path('Corporations'))) {
            File::deleteDirectory(app_path('Corporations'));
        }
    }

    public function testRun(): void
    {
      $source = __DIR__ . '/../resources/TestCorporation';
      $destination = app_path('Corporations/TestCorporation');
      File::copyDirectory($source, $destination);

      $this->assertFileExists($destination, 'State Machine folder could not be created successfully.');
      $this->assertFileExists($destination . '/' . 'setup.json', 'State Machine installation script missing.');

      $this->assertTrue(json_validate(file_get_contents($destination . '/' . 'setup.json', 'State Machine installation script not valid json.')));

      $this->artisan('lneuro:install', ['namespace' => 'TestCorporation'])
             ->expectsOutput('Your Corporation has been installed successfully.')
             ->assertSuccessful();

        $this->assertFileEquals($source . '/' . 'TestCorporation.php', 
                                $destination . '/' . 'TestCorporation.php');
        $this->assertFileExists($destination . '/' . 'Config.php');
        $this->assertFileEquals($source . '/' . 'Bootstrap.php', 
                                $destination . '/' . 'Bootstrap.php');

        $this->assertFileExists($destination . '/' . 'Database');
        $this->assertFileExists($destination . '/' . 'Database' . '/' . 'migrations');
        $this->assertFileExists($destination . '/' . 'Database' . '/' . 'Models');
        $this->assertFileEquals($source . '/' . 'Database' . '/' . 'Models' . '/' . 'TestModel.php', 
                                $destination . '/' . 'Database' . '/' . 'Models' . '/' . 'TestModel.php');
        $this->assertFileExists($destination . '/' . 'Transitions');
        $this->assertFileEquals($source . '/' . 'Transitions' . '/' . 'AudioTTStest.php', 
                                $destination . '/' . 'Transitions' . '/' . 'AudioTTStest.php');
        $this->assertFileEquals($source . '/' . 'Transitions' . '/' . 'ChatCompletionTest.php', 
                                $destination . '/' . 'Transitions' . '/' . 'ChatCompletionTest.php');
        $this->assertFileEquals($source . '/' . 'Transitions' . '/' . 'ImageGenerationTest.php', 
                                $destination . '/' . 'Transitions' . '/' . 'ImageGenerationTest.php');

      Config::set('laravelneuro.keychain.openai', 'fake-api-key');
      Config::set('laravelneuro.keychain.elevenlabs', 'fake-api-key');
      Storage::fake('lneuro');

      $this->artisan('lneuro:run', ['namespace' => 'TestCorporation', 'task' => 'Translate "This is a TTS test." into German.', '--debug' => true, '--with-migrations' => true])
      ->doesntExpectOutputToContain('The namespace you have passed does not point to a legal Corporation class.')
      ->doesntExpectOutputToContain('Failed to instantiate Corporation:')
      ->expectsOutput('Running Corporation migrations to ensure all required tables exist.')
      ->expectsOutput('Corporation successfully initiated. Passing task to new Project, please wait.')
      ->expectsOutput('Run complete. NetworkProject instance:');

      foreach(NetworkHistory::all() as $history)
      {
        echo $history->content . "\n";
      }

      $testModel = DB::table('test_models')->get()->last();

      $this->assertEquals('Dies ist ein TTS-Test.', $testModel->translation, 'The TestModel entry created by the TestCorporation state machine does not have the expected string in its "translation" field.');

      $this->assertFileEquals($source . '/' . 'resources' . '/' . 'testaudio1.mp3', 
                              Storage::disk('lneuro')->path($testModel->audioFile));

      $this->assertFileEquals($source . '/' . 'resources' . '/' . 'testimage1.png', 
                              Storage::disk('lneuro')->path($testModel->imageFile));

    }

}