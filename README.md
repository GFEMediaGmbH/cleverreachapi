# cleverreachapi
generic php class for interacting with cleverreach


## Main Usage

    composer require gfe/cleverreachapi

in your implementation

    use Gfe\Cleverreachapi\Apiconnector;
    ....
    $cleverapi = new Apiconnector($user , $pass); // the integration api credentials
    $status = $cleverapi->subscribeUser($email, $listId,$formId,'someInfoWhereThisComesFrom'); // email to subscribe to which list and what email form to send for double opt in

if you want to prevent getting a new token everytime

    $tokenObject = $cleverapi->getTokenObject();
    
    sample token
    {   "access_token":"xxx",
        "expires_in":2098276,
        "token_type":"bearer",
        "token_invalid_stamp":1697905128
    }

store this somewhere safe and you can set the token either via constructor

     $cleverapi = new Apiconnector($user , $pass , $tokenObject);

or explicit

    $tokenObject = $cleverapi->setTokenObject($tokenObject);

if token is no more valid api will create a new one that you have to save in your app ( see token_invalid_stamp )
so compare getTokenObject with your saved token


    
    
    
