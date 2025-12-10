<?php
$pie_data_aux = [];
foreach ($requests as $request)
{
    // if ($request["user_id"] == 361) continue; // Ocultar las solicitudes del usuario guest invitado
    $pie_data_aux[$request["status"]] += 1;
}
$pie_data = [];
foreach ($pie_data_aux as $category => $total)
{
    $pie_data[] = ["category" => $category, "value" => $total];
}

?>
<div class="card card-flush h-xl-100 pb-5 mb-5">

    <div class="card-header pt-7">

        <div>
            <h3 class="card-title align-items-start flex-column">
                <span class="card-label fw-bold text-gray-800 mb-5">De un vistazo: <span class="text-success" style="text-decoration: underline;"><?php echo count($requests) ?> solicitudes en total</span></span>
            </h3>
        </div>




    </div>

    <div class="card-body pt-2 card-scroll" style="min-height:32vh;max-height:32vh">
        <div class="row">
            <div class="col-sm-6" style="display:flex;flex-direction:row;justify-content:center;">
                <textarea style="display:none;" id="pie-chart-data"><?php echo json_encode($pie_data) ?></textarea>
                <canvas id="kt_chartjs_3" style="max-width:250px;max-height:250px;"></canvas>
            </div>
            <div class="col-sm-6" style="display:flex;flex-direction:column;gap:1rem;">
                <?php foreach ($pie_data as $pd) : ?>
                    <div style="display:flex;flex-direction:row;justify-content:space-between;">
                        <div><?php echo get_badge_html($pd["category"]) ?></div>
                        <div><?php echo $pd["value"] ?></div>
                    </div>
                <?php endforeach ; ?>
            </div>
        </div>
        
    </div>

</div>