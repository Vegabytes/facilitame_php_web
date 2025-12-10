(function ($)
{
    $(document).ready(function ()
    {

        var target = document.documentElement;
        var blockUI = new KTBlockUI(target, {
            message: "<b>Cerrando sesión</b>"
        });

        let logout_link = $("#logout");
        logout_link.on("click", logout);

        async function logout()
        {
            blockUI.block();

            let response;
            try
            {
                response = await $.post("api/logout", {}).fail(() => { return; });
                response = JSON.parse(response);
                if (response.status == "ok")
                {
                    location.href = "login";
                }
            } catch (error)
            {
                console.log(`error:`);
                console.log(error);
                return;
            }
            finally
            {
                blockUI.release();
            }
        }

    });
})(jQuery)