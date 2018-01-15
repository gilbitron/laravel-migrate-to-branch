# Laravel migrate:to-branch

This is a Laravel Artisan command to rollback migrations before switching to a given branch.

Imagine the scenario where you are working on a feature branch with some new migrations that have
been run on the database. Now you want to switch back to the develop branch but you need to
rollback the migrations to the state they were on the develop branch. This command makes this process
easier by working out which migrations need rolled back and then running the `migrate:rollback` command
for you.

**Note:** This command needs run _before_ you switch branches.

## Install

Require the library by running:

```
composer require gilbitron/laravel-migrate-to-branch
```

Next you need to add the following to your `providers` array in `config/app.php`:

```
Gilbitron\Laravel\MigrateToBranchServiceProvider::class
```

## Usage

Before switching to a different branch run the following command using the name of the destination branch:

```
php artisan migrate:to-branch {branch}
```

If you want to see which migrations need rolled back without actually running the `migrate:rollback` command
you can use the `--dry-run` flag.

## Credits

Laravel "migrate:to-branch" was created by [Gilbert Pellegrom](https://gilbitron.me) from [Dev7studios](https://dev7studios.co). Released under the MIT license.
