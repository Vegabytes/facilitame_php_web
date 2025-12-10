(function ($)
{
    $(document).ready(function ()
    {

        console.log(`%c  notifications()  `, `background: #004080; color: white`); // Blue / White // DEV

        let notifications = $(".notification-link");
        notifications.on("click", function (e)
        {
            bold_manage_notification.call(this, e);
        });

        async function bold_manage_notification(e)
        {
            e.preventDefault();
                        
            if ($(this).data("notification-status") == 0)
            {
                let response;
                try
                {
                    let data = {
                        notification_id: $(this).data("notification-id")
                    };
                    response = await $.post("api/notification-mark-read", data).fail(() => { return; });
                    response = JSON.parse(response);
                    if (response.status == "ok")
                    {
                        // Swal.fire({
                        //     icon: "success",
                        //     html: response.message_html,
                        //     buttonsStyling: false,
                        //     confirmButtonText: "Cerrar",
                        //     customClass: {
                        //         confirmButton: "btn btn-primary"
                        //     }
                        // });

                        // if (reload == 1)
                        // {
                        //     setTimeout(() =>
                        //     {
                        //         location.reload();
                        //     }, 4000);
                        // }
                    }
                    else
                    {
                        // Swal.fire({
                        //     icon: "warning",
                        //     html: response.message_html,
                        //     buttonsStyling: false,
                        //     confirmButtonText: "Cerrar",
                        //     customClass: {
                        //         confirmButton: "btn btn-primary"
                        //     }
                        // });
                    }
                } catch (error)
                {
                    // Swal.fire({
                    //     icon: "warning",
                    //     html: "Ha ocurrido un error",
                    //     buttonsStyling: false,
                    //     confirmButtonText: "Cerrar",
                    //     customClass: {
                    //         confirmButton: "btn btn-primary"
                    //     }
                    // });
                    return;
                } finally
                {
                    // let dismiss = form.find(".btn.dismiss");
                    // dismiss.click();
                }
            }

            location.href = $(this).attr("href");
        }

        $(document).on("click", ".notifications-mark-read", notifications_mark_read);
        async function notifications_mark_read()
        {
            let response;
            try
            {
                let data = {};
                response = await $.post("api/notifications-mark-read", data).fail(() => { return; });
                response = JSON.parse(response);
                if (response.status == "ok")
                {
                    $(".notification-link").removeClass("fw-bold");
                    $(".notification-link").addClass("fw-light");
                    $("#notification-indicator").remove();
                }
                else
                {
                    Swal.fire({
                        icon: "warning",
                        html: response.message_html,
                        buttonsStyling: false,
                        confirmButtonText: "Cerrar",
                        customClass: {
                            confirmButton: "btn btn-primary"
                        }
                    });
                }
            } catch (error)
            {
                Swal.fire({
                    icon: "warning",
                    html: "Se ha producido un error",
                    buttonsStyling: false,
                    confirmButtonText: "Cerrar",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    }
                });
                return;
            }
        }

    });
})(jQuery)