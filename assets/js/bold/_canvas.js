(function ($)
{
    $(document).ready(function ()
    {
        console.log(`%c  _canvas  `, `background: #222; color: #bada55`); // Black / Green // DEV

        $(document).on("click", "#btn-canvas", bold_canvas);
        async function bold_canvas(e)
        {
            console.log(`%c  bold_canvas()  `, `background: #004080; color: white`); // Blue / White // DEV
            e.preventDefault();

            const data = {
                name: "Stephen",
                email: "cueto.ig@gmail.com"
            };
            let response;
            try
            {
                response = await $.post("api/_canvas", data).fail(() => { return; });
                response = JSON.parse(response);
                if (response.status == "ok")
                {
                    console.log(`%c  OK  `, `background: #222; color: #bada55`); // Black / Green // DEV
                }
                else
                {
                    console.log(`%c  KO  `, `background: #CC0000; color: white`); // Red / White // DEV
                }
            } catch (error)
            {
                alert("1002680895: Ha ocurrido un error");
                return;
            }
        }

    });
})(jQuery)