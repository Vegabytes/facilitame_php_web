<?php
class User implements jsonSerializable {
    
    public $role;
    public $view;
    public $id;
    public $name;
    public $lastname;
    public $email;
    public $profile_picture;
    public $phone;
    public $allow_invoice_access;
    public $allow_invoice_access_granted_at;    
    public $code;
    public $requests = [];
    public $nif_cif;

    public function __construct($id = 0)
    {
        global $pdo;
        global $decoded;


        $user_id = ($id !== 0) ? $id : $decoded->user_id;

        $query = "SELECT users.*, roles.name AS role_name, pictures.filename AS profile_picture
        FROM `users`
        LEFT JOIN user_pictures pictures ON pictures.user_id = users.id
        JOIN model_has_roles mhr ON mhr.model_id = users.id
        JOIN roles ON roles.id = mhr.role_id
        WHERE 1
        AND users.id = :user_id";
    
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":user_id", $user_id);
        $stmt->execute();
        $user = $stmt->fetch();

        $this->role = $user["role_name"];
        $this->view = (in_array($user["role_name"], ["autonomo", "empresa", "particular"])) ? "cliente" : $user["role_name"];
        $this->id = $user["id"];
        $this->name = $user["name"];
        $this->lastname = $user["lastname"];
        $this->email = $user["email"];
        $this->profile_picture = $user["profile_picture"];
        $this->phone = $user["phone"];
        $this->allow_invoice_access = $user["allow_invoice_access"];
        $this->allow_invoice_access_granted_at = $user["allow_invoice_access_granted_at"];
        $this->nif_cif = $user["nif_cif"];


        if ($this->role == "comercial")
        {
            $query = "SELECT code FROM `sales_codes` WHERE user_id = :sales_rep_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindValue(":sales_rep_id", $this->id);
            $stmt->execute();
            $code = $stmt->fetch();

            if ($code)
            {
                $this->code = $code["code"];
            }
        }
    }

    public function jsonSerialize(): array
    {
        $profile_picture = null;
        if (!empty($this->profile_picture)) {
            $picture_path = ROOT_DIR . "/" . MEDIA_DIR . "/" . $this->profile_picture;
            if (file_exists($picture_path)) {
                $profile_picture = "data:image/jpeg;base64," . base64_encode(file_get_contents($picture_path));
            }
        }

        return [
            "id" => $this->id,
            "role" => $this->role,
            "role_name" => $this->role,
            "view" => $this->view,
            "name" => $this->name,
            "lastname" => $this->lastname,
            "email" => $this->email,
            "profile_picture" => $profile_picture,
            "phone" => $this->phone,
            "allow_invoice_access" => $this->allow_invoice_access,
            "allow_invoice_access_granted_at" => $this->allow_invoice_access_granted_at,
            "code" => $this->code,
            "nif_cif" => $this->nif_cif,
            "requests" => $this->requests
        ];
    }

    public function getRequests(): array
    {
        global $pdo;

        $query = "SELECT req.*, cat.name  AS category_name, sta.status_name AS status
        FROM `requests` req
        LEFT JOIN `categories` cat ON cat.id = req.category_id
        LEFT JOIN `requests_statuses` sta ON sta.id = req.status_id
        WHERE req.user_id = :user_id AND req.deleted_at IS NULL ORDER BY req.updated_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":user_id", $this->id);
        $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $res;
    }



    public function getCategoryIds(): array
    {
        global $pdo;

        if ($this->role != "proveedor")
        {
            return [];
        }

        $query = "SELECT category_id FROM `provider_categories` WHERE provider_id = :provider_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":provider_id", $this->id);
        $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $res;
    }

    public function getCustomers(): array
    {
        global $pdo;

        if ($this->role != "proveedor")
        {
            return [];
        }

        $category_ids = $this->getCategoryIds();
        $placeholders = implode(",", array_fill(0, count($category_ids), "?"));
        $query = "SELECT DISTINCT(user_id) FROM `requests` WHERE category_id IN ($placeholders)";
        $stmt = $pdo->prepare($query);        
        $stmt->execute($category_ids);
        $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $customer_ids = implode(",", $customer_ids);

        $query = "SELECT u.*, r.name AS role_name
        FROM `users` u 
        LEFT JOIN model_has_roles mhr ON mhr.model_id = u.id
        LEFT JOIN roles r ON mhr.role_id = r.id
        WHERE u.id IN ($customer_ids) ORDER BY u.lastname ASC, u.name ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $customers = $stmt->fetchAll();

        return $customers;
    }

    public function hasCustomer(int $customerId): bool
    {
        if ($this->role != "proveedor")
        {
            return false;
        }

        $customers = $this->getCustomers();

        $customerIds = array_map(function($c){return $c["id"];}, $customers);
        
        return in_array($customerId, $customerIds);
    }
    

    public function getCustomerRequests(int $customerId)
    {
        global $pdo;

        if (!$this->hasCustomer($customerId) && !admin())
        {
            return false;
        }

        $categoryIds = implode(",", $this->getCategoryIds());
        if ($categoryIds != "")
        {
            $categoryIds = " AND req.category_id IN ($categoryIds) ";
        }

        $query = "SELECT req.*, cat.name  AS category_name, sta.status_name AS status
        FROM `requests` req
        LEFT JOIN `categories` cat ON cat.id = req.category_id
        LEFT JOIN `requests_statuses` sta ON sta.id = req.status_id
        WHERE 1 $categoryIds AND req.user_id = :user_id AND req.deleted_at IS NULL ORDER BY req.request_date DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":user_id", $customerId);
        $stmt->execute();
        $customerRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $customerRequests;
    }




    // 2025-01-21 :: inicio
    public function getRequestsV2(): array
    {
        global $pdo;

        switch ($this->role)
        {
            case 'comercial':

                $query = "SELECT id FROM sales_codes WHERE user_id = :user_id";
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(":user_id", $this->id);
                $stmt->execute();
                $code_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $code_ids = implode(",", $code_ids);

                $query = "SELECT customer_id FROM `customers_sales_codes` WHERE sales_code_id IN ($code_ids)";                
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
                if (empty($customer_ids))
                {   
                    return [];
                }
        
                $customer_ids = implode(",", $customer_ids);
        
                $query = "SELECT req.*, cat.name  AS category_name, sta.status_name AS status
                FROM `requests` req
                LEFT JOIN `categories` cat ON cat.id = req.category_id
                LEFT JOIN `requests_statuses` sta ON sta.id = req.status_id
                WHERE req.user_id IN ($customer_ids) AND req.deleted_at IS NULL ORDER BY req.request_date DESC";
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $requests = $stmt->fetchAll();
                
                foreach ($requests as $i => $request)
                {
                    $requests[$i]["request_info"] = get_request_category_info($request);
                    
                    if (!is_null($request["commision_type"]))
                    {
                        $requests[$i]["commision_type_name"] = getCommissionTypeName($request["commision_type"]);
                    }
                }

                return $requests;
                break;
            
            default:
                return [];
                break;
        }
    }
    // 2025-01-21 :: fin



    // 2025-01-21 :: inicio
    public function getCustomersV2(): array
    {
        global $pdo;

        $customers = [];

        switch ($this->role)
        {
            case 'comercial':

                $query = "SELECT id FROM sales_codes WHERE user_id = :user_id";
                $stmt = $pdo->prepare($query);
                $stmt->bindValue(":user_id", $this->id);
                $stmt->execute();
                $code_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $code_ids = implode(",", $code_ids);

                $query = "SELECT customer_id FROM `customers_sales_codes` WHERE sales_code_id IN ($code_ids)";                
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $customer_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (empty($customer_ids))
                {
                    return [];
                }

                foreach ($customer_ids as $customer_id)
                {                    
                    $customers[] = new User($customer_id);
                }
                break;
            
            default:                
                break;
        }

        return $customers;
    }
    // 2025-01-21 :: fin




    // 2025-01-21 :: inicio
    public function getExcludedServices(): array
    {
        global $pdo;

        if ($this->role != "comercial")
        {
            return [];
        }
    
    
        $query = "SELECT category_id FROM `sales_rep_excludes_category` WHERE sales_rep_id = :sales_rep_id";
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(":sales_rep_id", $this->id);
        $stmt->execute();
        $excluded_services = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
        return ($excluded_services);
    }    
    // 2025-01-21 :: fin
}
// $user = new User();
?>