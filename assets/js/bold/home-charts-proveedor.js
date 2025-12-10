(function ($) {
    $(document).ready(function(){

        am5.ready(function () {
            var root = am5.Root.new("kt_amcharts_3");

            root.setThemes([
                am5themes_Animated.new(root)
            ]);

            var chart = root.container.children.push(am5percent.PieChart.new(root, {
                layout: root.verticalLayout
            }));

            var series = chart.series.push(am5percent.PieSeries.new(root, {
                alignLabels: true,
                // calculateAggregates: true,
                valueField: "value",
                categoryField: "category"
            }));

            series.slices.template.setAll({
                strokeWidth: 3,
                stroke: am5.color(0xffffff)
            });

            series.labelsContainer.set("paddingTop", 30);

            // Mostrar valores absolutos en las etiquetas
            series.labels.template.setAll({
                text: "{category}: {value}"
            });

            // Establecer datos
            const data = JSON.parse($("#pie-chart-data").val());

            series.data.setAll(data);

            series.appear(1000, 100);

        }); // end am5.ready()
    
    });
})(jQuery);
