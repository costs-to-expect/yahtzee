[![Minimum PHP Version](https://img.shields.io/badge/php-^8.2-8892BF.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://github.com/costs-to-expect/yahtzee/blob/main/LICENSE)
[![Laravel Forge Site Deployment Status](https://img.shields.io/endpoint?url=https%3A%2F%2Fforge.laravel.com%2Fsite-badges%2Fbf7e7ccf-6a96-4e8d-91ab-07c44754f4d0%3Fdate%3D1&style=plastic)](https://forge.laravel.com/servers/581137/sites/2028557)

# Yahtzee Game Scoring

## Overview

Game scoring for Yahtzee, powered by the Costs to Expect API.

![Score sheet](/resources/art/score-sheet.png)

## Other Apps

[Yatzy](https://github.com/costs-to-expect/yatzy)

We plan to create Apps for each of the Board and dice games we play, the Apps will all be Open Source, you 
are free to create your own and then submit a PR to the Costs to Expect [API](https://github.com/costs-to-expect/api) 
to add the new game type.

## Set up

I'm going to assume you are using Docker, if not, you should be able to work out what you need to run for your 
development setup.

Go to the project root directory and run the below.

### Environment

* $ `docker network create costs.network` *
* $ `docker compose build`
* $ `docker compose up`
* $ `docker exec yahtzee.app php artisan key:generate`

After generating the key, you need to restart your containers, so run down and up again to force the new key to be used.

* $ `docker exec yahtzee.app php artisan migrate:install`
* $ `docker exec yahtzee.app php artisan migrate`

*We include a network for local development purposes, I need to connect to a local version of the Costs to Expect
API, You probably don't need this so remove the network section from your docker compose file and don't create the
network.
