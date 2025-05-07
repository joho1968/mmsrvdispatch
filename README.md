# mmsrvdispatch
Simple Mattermost /slash service dispatcher and responder written in PHP, also featuring an extremely important /chuck command.

It has been tested with PHP 7.x and PHP 8.1; it has been tested with Mattermost up to version 10.7.x.

## Usage

This application is intended to be run as a "web service" in the regard that it receives a POST request (from Mattermost). How you do that is up to you. It should work with the Apache PHP module, fcgid, PHP-FPM, etc. The only _response_type_ used here is **ephemeral**. If you want the returned text to actually appear as a post, simply change to **in_channel**

## Background

Simply to start (or continue) building (yet another) PHP codebase for talking to Mattermost :) This script makes some assumptions that may not be safe or clever for your environment. I have tried to put comments near such code so that you can do something about it. If you think this code could be written in a better way, with cool classes and functions, you are not wrong. This is, at best, a seed. Make it pretty :-)

## Installation

As explained above, you need to make mmsrvdispatch.php reachable via an URL, such as https://mymmservice.com/mmsrvdispatch.php. Once you can verify that you can reach that URL from your Mattermost installation, you should be more or less good to go. You also need to create a /slash command in Mattermost. For example:

|Setting|Value|
|-------|-----|
|Display name|Chuck|
|Description|Chuck Norris joke|
|Command Trigger Word|chuck|
|Request URL|https://mymmservice.com/mmsrvdispatch.php?slash |
|Request method|POST|
|Autocomplete|(checked)|
|Autocomplete Hint|Chuck Norris Random Joke|
|Autocomplete Description|Display a random Chuck Norris joke, before he finds you|

When a user then types /chuck (or begins to type /ch...), the MM AutoComplete will display the command and its description. If the users selects and sends the command, the request is sent to the script, which dispatches it and returns data for Mattermost to display.

Commands implemented (you need to set-up one /slash hook for each in MM):

|Command|Function|
|-------|-----|
|/bold  |Dummy command that returns the typed text in bold|
|/time  |Returns time on (dispatch) server|
|/emo   |Displays a link to an emoji cheat sheet|
|/chuck |Returns a Chuck Norris joke from chucknorris.io|
|/chuck -cat |Returns list of Chuck Norris joke categories|
|/chuck <category> |Returns Chuck Norris joke from <category>|

Tailor to your own needs :)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details
