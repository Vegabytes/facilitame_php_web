<?php
class Status {

    public function __construct()
    {

    }

    public static function getAllKP()
    {
        global $pdo;

        $query = "SELECT id, status_name AS name FROM `requests_statuses` WHERE 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $statuses = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return $statuses;
    }

    public static function getStyles()
    {
        $shared = "font-size: 0.75rem; font-weight: 400; padding: 0.15rem 0.5rem; border-radius: 0.3rem; height: fit-content; background-color: ";
        return [
            1 => $shared . "#A3D2CA;",
            2 => $shared . "#FFE5B4;",
            3 => $shared . "#FFADAD;",
            4 => $shared . "#FFD6A5;",
            5 => $shared . "#FF6F91;",
            6 => $shared . "#FFDAC1;",
            7 => $shared . "#95FFA9;",
            8 => $shared . "#C7CEEA;",
        ];

        
    }
}
?>