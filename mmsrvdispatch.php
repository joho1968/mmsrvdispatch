<?php
/**
 * Simple Mattermost /slash service dispatcher and responder written in PHP
 * mmsrvdispatch.php
 *
 * Author Joaquim Homrighausen | <joho@boojam.se> | @joho68 | joho1968
 * Version 2017.03, tested with Mattermost 3.7.x
 *
 * Sponsored by WebbPlatsen i Sverige AB, Stockholm Sweden, www.webbplatsen.se
 *
 * If you are a DeltaFelter or if you break this code, you own all the pieces :)
 *
 * This application is intended to be run as a "web service" in the regard that
 * it receives a POST request (from Mattermost). How you do that is up to you.
 * It should work with the Apache PHP module, fcgid, PHP-FPM, etc.
 *
 * This script makes some assumptions that may not be safe or clever for your
 * environment. I have tried to put comments near such code so that you can do
 * something about it.
 *
 * The only response_type used here is ephemeral. If you want the returned text
 * to actually appear as a post, simply change to 'in_channel'.
 *
 * If you think this code could be written in a better way, with cool classes
 * and functions, you are not wrong. This is, at best, a seed. Make it pretty
 * :-)
 *
 *
 * MIT License
 * Copyright (c) 2017 ComXSentio AB; All rights reserved.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */


//The URL for the request in Mattermost should be:
//  https://this.server.com/mmsrvdispatch.php?slash
//It should be a POST request from Mattermost
//The ?slash is merely there to possibly avoid mistakes :)


//Comment this line if you don't want logging
define ('MMSRV_LOG', true);

//It may be a good idea to limit which IP addresses can make requests
$cfgHost = array ('allow.request.from.ip1', 'allow.request.from.ip2');
//or
$cfgHost = array (); //Disallow everyone :)
//or
$cfgHost = array ('*');//Special case, allow everyone

if (!in_array ('*', $cfgHost) &&
        !empty ($_SERVER) &&
            !empty ($_SERVER ['REMOTE_ADDR']) &&
                !in_array ($_SERVER ['REMOTE_ADDR'], $cfgHost))
    {
    //You may want to improve on this "not allowed page". One possibility is
    //of course to return a suitable JSON response that basically says
    //"Access denied"
    ?>
    <!DOCTYPE html>
    <html>
    <head>
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
      <meta http-equiv="pragma" content="no-cache" />
      <meta http-equiv="expires" content="-1" />
      <meta name="generator" content="Turbo Pascal 3.0" />
      <meta http-equiv="imagetoolbar" content="no" />
      <meta name="MSSmartTagsPreventParsing" content="true" />
      <meta name="robots" content="noindex,nofollow" />
      <title>You have arrived</title>
    </head>
    <body>
      <p>This page was generated with Turbo Pascal 3.0.</p>
      <p>If you have questions about this page, please feel free to get in touch with us.</p>
    </body>
    <?php
    die ();
    }

//Possibly log the request
if (defined ('MMSRV_LOG'))
    {
    //Figure out where we are. We try not to use things like realpath and other
    //"intelligent" functions since we may end up with open_basedir warnings and
    //what not. You may find this ridiculous, if so, just choose your poison :)
    
    if (defined ('__DIR__'))
        {
        $logfn = __DIR__.'/'.basename ($_SERVER ['SCRIPT_FILENAME'], '.php').'.log';
        }
    else
        {
        $logfn = dirname (__FILE__).'/'.basename ($_SERVER ['SCRIPT_FILENAME'], '.php').'.log';
        }

    //Log the request
    $fp = fopen ($logfn, 'ab');
    if (is_resource ($fp))
        {
        fprintf ($fp, "===== Request from %s at %s =====\n\n", $_SERVER ['REMOTE_ADDR'], strftime ('%Y%m%d.%H%M%S'));
        fwrite ($fp, "POST:\n");
        fwrite ($fp, var_export ($_POST, true));
        fprintf ($fp, "\n\n***\n\n");
        fwrite ($fp, "GET:\n");
        fwrite ($fp, var_export ($_GET, true));
        fprintf ($fp, "\n\n***\n\n");
        fwrite ($fp, "REQUEST:\n");
        fwrite ($fp, var_export ($_REQUEST, true));
        fprintf ($fp, "\n\n=================================\n\n");
        fclose ($fp);
        }
    else
        {
        die ('No log'); //Comment this out if it doesn't matter if logging fails
        }
    }//defined MMSRV_LOG

//Accept these Mattermost tokens
$cfgToken = array ('mattermosttoken1', 'mattermosttoken2');
//or
$cfgToken = array (); //Allow no access
//or
$cfgToken = array ('*'); //Accept any token (don't do this)

//Some sanity checking
if (empty ($_POST ['command']))
    die ();//Command not set
if (empty ($_POST ['token']))
    die ();//Token not set
if (!in_array ('*', $cfgToken) && !in_array ($_POST ['token'], $cfgToken))
    die ();//Token not in allowed list

//Commands implemented:
//
// /bold  Returns bold text
// /time  Returns current time (on dispatch server)
// /emo   Returns link to emojis supported in MM (and others)
// /chuck Returns the a Chuck Norris quote (from The Internet Chuck Norris Database)
//

switch ($_POST ['command'])
    {
    case '/bold'://Usage /bold <theText>
        if (!empty ($_POST ['text']))
            $theText = '**'.trim ($_POST ['text']).'** :sheep:';
        else
            $theText = 'Sorry, I need **something** to work with!';
        $response = array (
            'response_type' => 'ephemeral',
            'text' => $theText,
            'username' => 'mmsrvdispatch',
            );
        header ('Content-type: application/json');
        echo json_encode ($response);
        break;
    case '/time'://Usage /time
        $response = array (
            'response_type' => 'ephemeral',
            'text' => 'Earth :watch: for dispatch server is '.strftime ('%Y-%m-%d %H:%M:%S'),
            'username' => 'mmsrvdispatch',
            );
        header ('Content-type: application/json');
        echo json_encode ($response);
        die ();
        break;
    case '/emo'://Usage /emo
        $theText = 'There are too many emojis to display them all here, but if you use this link, you can find them all :smile:'.
                   "\n".
                   '[Emoij cheat sheet](https://www.webpagefx.com/tools/emoji-cheat-sheet/)'.
                   "\n".
                   "(Please note that you type the emoticon shortcode without spaces.)\n".
                   "\n".
                   '|Text     |Symbol |Text     |Symbol|'.
                   "\n".
                   '|---------|-------|---------|------|'.
                   "\n".
                   '|: sheep :|:sheep:|: smile :|:smile:|'.
                   "\n".
                   '';
        $response = array (
            'response_type' => 'ephemeral',
            'text' => $theText,
            'username' => 'mmsrvdispatch',
            );
        header ('Content-type: application/json');
        echo json_encode ($response);
        die ();
        break;
    case '/chuck':
        $chucknorris = @file_get_contents ('http://api.icndb.com/jokes/random');
        if ($chucknorris === false)
            $theText = 'Unable to fetch random Chuck Norris joke, maybe he is angry today?';
        else
            {
            $jsonText = json_decode ($chucknorris, true);
            if (!is_array ($jsonText))
                $theText = 'Unable to decode random Chuck Norris joke, maybe he is secret today?';
            else
                {
                if (!empty ($jsonText ['type'])
                        && $jsonText ['type'] == 'success'
                            && !empty ($jsonText ['value']['joke']))
                    {
                    $theText = $jsonText ['value']['joke']."\nCourtesy of http://www.icndb.com :smile:\n";
                    }
                else
                    {
                    $theText = 'No random Chuck Norris joke found, the joke may be you?';
                    }
                }
            }
        $response = array (
            'response_type' => 'ephemeral',
            'text' => $theText,
            'username' => 'chucknorris',
            );
        header ('Content-type: application/json');
        echo json_encode ($response);
        die ();
        break;
    default:
        $response = array (
            'response_type' => 'ephemeral',
            'text' => 'Sorry, we have not implemented '.$_POST ['command'],
            'username' => 'mmsrvdispatch',
            );
        header ('Content-type: application/json');
        echo json_encode ($response);
        die ();
        break;
    }//switch

?>