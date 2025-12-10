<?php
if (!IS_MOBILE_APP)
{
    header("HTTP/1.1 404");
    exit;
}

$data = [];

try
{
    $profile_picture = USER["profile_picture"] == "" ? "profile-default.jpg" : USER["profile_picture"];
    $profile_picture = ROOT_URL . "/" . MEDIA_DIR . "/" . $profile_picture;

    json_response("ok", "", 1184973562, $profile_picture);
}
catch (Exception $e)
{
    if (DEBUG === true)
    {
        json_response("ko", $e->getMessage(), 2995213154);
    }
    else
    {
        json_response("ko", "", 3740351091);
    }
}

?>