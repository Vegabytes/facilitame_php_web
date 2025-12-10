<?php
$data = [];
$categories = Category::getAll();
$userServices = $user->getServices();

foreach ($userServices as $i => $userService)
{
    foreach ($categories as $category)
    {
        if ($category["id"] != $userService["category_id"]) continue;        
        $userServices[$i]["category_name"] = $category["name"];
    }
}

$data = $userServices;
json_response("ok", "", 2587777212, $data);
?>