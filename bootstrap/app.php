<?php

/*
|--------------------------------------------------------------------------
| Keep Another App's Environment Out Of This One
|--------------------------------------------------------------------------
|
| This must run before anything reads the environment.
|
| Laravel's env repository writes every .env value through putenv() and is
| IMMUTABLE - it will not overwrite a variable that already exists. On a
| server where several Laravel apps share one Apache/mod_php process pool,
| that combination leaks: a worker thread that has served another app still
| holds that app's variables, and when a request for this app lands on the
| same thread, Dotenv sees them already set and declines to replace them.
|
| This machine runs this app and AniSystem side by side on one Apache, and
| the leak was measurable: identical requests came back with two different
| session lifetimes (2880 from this .env, 480 from AniSystem's), and a
| session cookie minted by one worker was rejected by 12 of the next 15
| requests because those workers were encrypting with the wrong APP_KEY.
| The symptom was being bounced to /login every couple of minutes.
|
| Disabling the putenv adapter makes this app read and write only $_ENV and
| $_SERVER, which are rebuilt per request, so nothing another app leaves in
| the process environment can reach it - and it leaves nothing behind either.
| getenv() stops seeing these values, which is the intended trade: nothing in
| this codebase reads configuration that way.
|
*/

Illuminate\Support\Env::disablePutenv();

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
