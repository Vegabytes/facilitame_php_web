<?php
$data = [];

// $categories = Category::getNames();
$categoriesIcons = Category::getIcons();

// $statuses = Status::getAllKP();
$statusStyles = Status::getStyles();

$userRequests = $user->getRequests();

foreach ($userRequests as $i => $userRequest)
{
    // $userRequests[$i]["category_name"] = $categories[$userRequest["category_id"]];
    $userRequests[$i]["details"] = get_request_category_info($userRequest);
    // $userRequests[$i]["status"] = $statuses[$userRequest["status_id"]];
    $userRequests[$i]["status_style"] = $statusStyles[$userRequest["status_id"]];
    $userRequests[$i]["icon"] = $categoriesIcons[$userRequest["category_id"]];
}

if (!empty($_POST["query"]))
{
    $searchTargets = [
        "id" => "",
        "category_name" => "",
        "details" => "",
        "form_values" => "decode",
        "status" => ""
    ];

    foreach ($userRequests as $i => $userRequest)
    {
        $found = false;

        foreach ($searchTargets as $index => $action)
        {
            if ($action == "")
            {
                if (strpos($userRequest[$index], $_POST["query"]) !== false)
                {
                    $found = true;
                    break;
                }
            }
            else
            {                
                $formValues = json_decode($userRequest["form_values"], true);
                if (gettype($formValues) != "array") $formValues = json_decode($formValues, true);

                foreach ($formValues as $fv)
                {
                    if (stripos($fv["value"], $_POST["query"]) !== false)
                    {
                        $found = true;
                        break;
                    }
                }
            }
        }

        if ($found === false)
        {
            unset($userRequests[$i]);
        }
    }
}

$data = $userRequests;
json_response("ok", "", 2587777212, $data);
?>