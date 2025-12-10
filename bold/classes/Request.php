<?php
class Request {

    public $id;
    public $category_id;
    public $user_id;
    public $call_providers;
    public $code;
    public $allow_call;
    public $form_values;
    public $status_id;
    public $request_date;
    public $commision_type;
    public $commision;
    public $created_at;
    public $updated_at;
    public $deleted_at;

    public function __construct($requestId)
    {
        global $pdo;
        global $user;        

        $query = "SELECT * FROM `requests` WHERE id = :requestId AND user_id = :userId";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":requestId", $requestId);
        $stmt->bindValue(":userId", $user->id);
        $stmt->execute();
        $request = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($request))
        {
            json_response("ko", "Invalid", 457343127);
        }

        $request = $request[0];

        $this->id = $request["id"];
        $this->category_id = $request["category_id"];
        $this->user_id = $request["user_id"];
        $this->call_providers = $request["call_providers"];
        $this->code = $request["code"];
        $this->allow_call = $request["allow_call"];
        $this->form_values = $request["form_values"];
        $this->status_id = $request["status_id"];
        $this->request_date = $request["request_date"];
        $this->commision_type = $request["commision_type"];
        $this->commision = $request["commision"];
        $this->created_at = $request["created_at"];
        $this->updated_at = $request["updated_at"];
        $this->deleted_at = $request["deleted_at"];
    }

    // public function jsonSerialize(): array
    // {
    //     return [
    //         "role" => $this->role,
    //         "view" => $this->view,
    //         "name" => $this->name,
    //         "lastname" => $this->lastname,
    //         "email" => $this->email,
    //         "profile_picture" => "data:image/jpeg;base64," . base64_encode(file_get_contents(ROOT_DIR . "/" . MEDIA_DIR . "/" . $this->profile_picture)),
    //         "phone" => $this->phone
    //     ];
    // }

}
// $user = new User();
?>