<?php
/** @var \Laravel\Boost\Install\GuidelineAssist $assist */
?>
# Laravel 11
<?php if($assist->hasMcpEnabled()): ?>
- CRITICAL: ALWAYS use ___SINGLE_BACKTICK___search-docs___SINGLE_BACKTICK___ tool for version-specific Laravel documentation and updated code examples.
<?php endif; ?>
<?php if(file_exists(app_path('Http/Kernel.php'))): ?>
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel 11 file structure.
- This is perfectly fine and recommended by Laravel. Follow the existing structure from Laravel 10. We do not need to migrate to the Laravel 11 structure unless the user explicitly requests it.

## Laravel 10 Structure
- Middleware typically lives in ___SINGLE_BACKTICK___<?php echo e($assist->appPath('Http/Middleware/')); ?>___SINGLE_BACKTICK___ and service providers in ___SINGLE_BACKTICK___<?php echo e($assist->appPath('Providers/')); ?>___SINGLE_BACKTICK___.
- There is no ___SINGLE_BACKTICK___bootstrap/app.php___SINGLE_BACKTICK___ application configuration in a Laravel 10 structure:
    - Middleware registration is in ___SINGLE_BACKTICK___<?php echo e($assist->appPath('Http/Kernel.php')); ?>___SINGLE_BACKTICK___
    - Exception handling is in ___SINGLE_BACKTICK___<?php echo e($assist->appPath('Exceptions/Handler.php')); ?>___SINGLE_BACKTICK___
    - Console commands and schedule registration is in ___SINGLE_BACKTICK___<?php echo e($assist->appPath('Console/Kernel.php')); ?>___SINGLE_BACKTICK___
    - Rate limits likely exist in ___SINGLE_BACKTICK___RouteServiceProvider___SINGLE_BACKTICK___ or ___SINGLE_BACKTICK___<?php echo e($assist->appPath('Http/Kernel.php')); ?>___SINGLE_BACKTICK___
<?php else: ?>
- Laravel 11 brought a new streamlined file structure which this project now uses.

## Laravel 11 Structure
- In Laravel 11, middleware are no longer registered in ___SINGLE_BACKTICK___<?php echo e($assist->appPath('Http/Kernel.php')); ?>___SINGLE_BACKTICK___.
- Middleware are configured declaratively in ___SINGLE_BACKTICK___bootstrap/app.php___SINGLE_BACKTICK___ using ___SINGLE_BACKTICK___Application::configure()->withMiddleware()___SINGLE_BACKTICK___.
- ___SINGLE_BACKTICK___bootstrap/app.php___SINGLE_BACKTICK___ is the file to register middleware, exceptions, and routing files.
- ___SINGLE_BACKTICK___bootstrap/providers.php___SINGLE_BACKTICK___ contains application specific service providers.
- No app\Console\Kernel.php - use ___SINGLE_BACKTICK___bootstrap/app.php___SINGLE_BACKTICK___ or ___SINGLE_BACKTICK___routes/console.php___SINGLE_BACKTICK___ for console configuration.
- Commands auto-register - files in ___SINGLE_BACKTICK___<?php echo e($assist->appPath('Console/Commands/')); ?>___SINGLE_BACKTICK___ are automatically available and do not require manual registration.
<?php endif; ?>

## Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: ___SINGLE_BACKTICK___$query->latest()->limit(10);___SINGLE_BACKTICK___.

### Models
- Casts can and likely should be set in a ___SINGLE_BACKTICK___casts()___SINGLE_BACKTICK___ method on a model rather than the ___SINGLE_BACKTICK___$casts___SINGLE_BACKTICK___ property. Follow existing conventions from other models.

## New Artisan Commands
- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
    - ___SINGLE_BACKTICK___<?php echo e($assist->artisanCommand('make:enum')); ?>___SINGLE_BACKTICK___
    - ___SINGLE_BACKTICK___<?php echo e($assist->artisanCommand('make:class')); ?>___SINGLE_BACKTICK___
    - ___SINGLE_BACKTICK___<?php echo e($assist->artisanCommand('make:interface')); ?>___SINGLE_BACKTICK___
<?php /**PATH C:\Users\User\Desktop\Web_Apps\Sample_System_laravel_Apps\project-management-system\storage\framework\views/941b2b8f6b239f9d5b2e62ae0beddef1.blade.php ENDPATH**/ ?>