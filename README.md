# PHP Tree Generation Test Task

## Usage

1. Clone: `git clone git@github.com:ykvdev/test-php-gentree.git && cd ./test-php-gentree`
1. Install dependencies: `composer install`
1. Run tests: `./vendor/bin/phpunit`
1. Generate tree: `php ./app/console/run gentree -i ./data/input.example.csv -o ./data/output.json`
1. See results: `cat ./data/output.json | less`

## Task Description

See: [task.txt](./task.txt)