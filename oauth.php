<?php
//------------------------------
// Display an authorization page
//------------------------------
if($_SERVER['REQUEST_METHOD'] == 'GET') {
    file_put_contents('/tmp/redirectUrl', $_GET['success_call_back']);
?>
<html>
<head></head>
<body>
<form action="/oauth.php" method="post">
    <p>Click Authorize to get your app connected son!</p>
    <input type="hidden" name="authorized" value="yes"/>
    <input type="submit" value="Authorize"/>
</body>
<?php
} elseif($_SERVER['REQUEST_METHOD']) {
    //-----------------------------------
    // Capture OAuth info sent by Magento
    //-----------------------------------
    if(isset($_POST['oauth_consumer_key'])) {
        // Configuration
        $data['mageUrl']        = $_POST['http://dev.myclickbazaar.com'];
        $data['consumerKey']    = $_POST['a0fad51a6016beb7b397650fe76028b1'];
        $data['consumerSecret'] = $_POST['caf5c8358d3f0e64120bdf105b46cd29'];
        $data['verifier']       = $_POST['oauth_verifier'];
        file_put_contents('/tmp/oauth-info', serialize($data));
    }

    //---------------------------------------------
    // We have approval, let's get the access token
    //---------------------------------------------
    elseif(isset($_POST['authorized']) && $_POST['authorized'] == 'yes') {
        $data = unserialize(file_get_contents('/tmp/oauth-info'));
        $requestTokenRequestUrl = $data['mageUrl'] . 'oauth/token/request';
        $accessTokenRequestUrl  = $data['mageUrl'] . 'oauth/token/access';

        // Instantiate the OAuth client
        $oauthClient = new OAuth($data['consumerKey'], $data['consumerSecret']);

        try {
            // Fetch a request token and redirect to the magento site for authorization
            $requestToken = $oauthClient->getRequestToken($requestTokenRequestUrl);

            // Fetch an access token
            $oauthClient->setToken($requestToken['oauth_token'], $requestToken['oauth_token_secret']);
            $accessToken = $oauthClient->getAccessToken($accessTokenRequestUrl, null, $data['verifier']);

            // Redirect to Magento
            $redirectUrl = trim(file_get_contents('/tmp/redirectUrl'));
            unlink('/tmp/redirectUrl');
            unlink('/tmp/oauth-info');

            header('Location: ' . $redirectUrl);
        } catch (OAuthException $e) {
            ob_start();
            var_dump($e);
            $details = ob_get_clean();
            echo '<pre>';
            echo '<h2>Error Message</h2>';
            echo $e->getMessage();
            echo '<hr><h3>Details</h3>';
            echo $details;
            echo '</pre>';
        }
    }
}