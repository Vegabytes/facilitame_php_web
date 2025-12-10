(function ($) {
    $(document).ready(function(){
    
        console.log(`%c  home-charts-proveedor-apexcharts  `, `background: #222; color: #bada55`); // Black / Green // DEV

        var ctx = document.getElementById('kt_chartjs_3').getContext('2d');

        console.log({KTUtil});        
        

        // Define colors
        // var primaryColor = KTUtil.getCssVariableValue('--kt-primary');
        var dangerColor = KTUtil.getCssVariableValue('--kt-danger');
        var successColor = KTUtil.getCssVariableValue('--kt-success');
        var warningColor = KTUtil.getCssVariableValue('--kt-warning');
        var infoColor = KTUtil.getCssVariableValue('--kt-info');

        // Define fonts
        var fontFamily = KTUtil.getCssVariableValue('--bs-font-sans-serif');

        // Chart labels
        const labels = [
            "Oferta Disponible", // #F6C00
            "Activada", // #DFFFEA
            "En curso", // #F8F5FF
            "Revisión solicitada", // #FFEEF3
            "Iniciado" // #cacaca
        ];

        // Chart data
        const data = {
            labels: labels,
            datasets: [{
                label: 'Solicitudes',
                data: [11, 14, 1, 1, 6], // Valores absolutos
                backgroundColor: ["#F6C000", "#17C653", "#7239EA", "#F8285A", "#CACACA"], // Colores de cada porción
                borderColor: KTUtil.getCssVariableValue('--kt-gray-200'), // Borde
                borderWidth: 0 // Grosor del borde
            }]
        };

        // Chart config
        const config = {
            type: 'pie',
            data: data,
            options: {
                plugins: {
                    legend: {
                        position: 'bottom', // Posición de la leyenda
                        labels: {
                            font: {
                                family: fontFamily // Fuente personalizada
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (tooltipItem) {
                                let label = data.labels[tooltipItem.dataIndex] || '';
                                let value = data.datasets[0].data[tooltipItem.dataIndex];
                                return `${label}: ${value}`; // Muestra valores absolutos
                            }
                        }
                    },
                    title: {
                        display: false
                    }
                },
                responsive: true
            }
        };

        // Init ChartJS
        var myChart = new Chart(ctx, config);
    
    });
})(jQuery)