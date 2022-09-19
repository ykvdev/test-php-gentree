# PHP Tree Generation Test Task

## Usage

1. Clone: `git clone https://bitbucket.org/atoumus/test_php_gentree.git && cd ./test_php_gentree`
1. Install dependencies: `composer install`
1. Run tests: `./vendor/bin/phpunit`
1. Generate tree: `php ./app/console/run gentree -i ./data/input.example.csv -o ./data/output.json`
1. See results: `cat ./data/output.json | less`

## Task Description

See: [task.txt](./task.txt)