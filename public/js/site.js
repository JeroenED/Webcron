$(document).ready(function() {
    $("body").on("click", "#patternDropdown li", function() {
        if(this.value != "custom") { $("input#delay").val($(this).data("val")); }
    });
    $('#nextrunselector').datetimepicker({format: 'L LTS'});
    $('#lastrunselector').datetimepicker({format: 'L LTS'});
   
    $("body").on("click", ".runcron", function() {
        $("#ajax_loader").show();
        fullurl = "/runnow.php?jobID=" + $(this).data("id");
        $.ajax(fullurl).done(function(data) {
            results = JSON.parse(data);
           
            if(results["error"] !== undefined) {
                $("#resulttitle").html("Error");
                $("#resultbody").text(results["error"]);
            } else {
                $("#resulttitle").html("Success");
                $("#resultbody").text(results["message"]);
            }
            $("#ajax_loader").hide();
            $('#resultmodal').modal('show');
        });
    });
    $("body").on("input", "input[name=url]", function() {
        if($("input[name=url]").val().startsWith("reboot")) {
            $("#url-description").html("This job triggers a reboot. Please use <pre>reboot cmd={{command}}&services={{command}}</pre> to modify the reboot and get services commands. You can use {s}+ or {m}+ in the reboot command to use the Reboot wait configuration value ({s}+ will convert to seconds, {m}+ to minutes)");
            $("label[for=expected]").html("Capture services after reboot? (1: yes; 0: no)");
            $("input[name=expected]").attr("placeholder", "1");
        } else {
            $("#url-description").html("");
            $("label[for=expected]").html("Expected exit code");
            $("input[name=expected]").attr("placeholder", "200");
        }
    });
    $("input[name=url]").trigger("input");
});