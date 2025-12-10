<?php
$user = new User();

$customerId = $_POST["customerId"];

if (!$user->hasCustomer($customerId))
{
    json_response("ko", "No puedes gestionar las solicitudes de este cliente", 2601385273);
}

$customerRequests = $user->getCustomerRequests($customerId);
foreach ($customerRequests as $i => $request)
{
    $customerRequests[$i]["request_info"] = get_request_category_info($request);
    $customerRequests[$i]["request_date"] = is_null($request["request_date"]) ? "-" : fdate($request["request_date"]);
    $customerRequests[$i]["updated_at"] = is_null($request["updated_at"]) ? "-" : fdate($request["updated_at"]);
}

json_response("ok", "", 720760515, $customerRequests);
?>