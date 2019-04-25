/* 
 * The MIT License
 *
 * Copyright 2017 Jeroen De Meerleer <me@jeroened.be>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */


$(document).ready(function() {
    $("body").on("click", "#patternDropdown li", function() {
        if(this.value != "custom") { $("input#delay").val($(this).data("val")); }
    });
    $('#nextrunselector').datetimepicker( { format: 'DD/MM/YYYY HH:mm:ss' } );
   
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
    $("body").on("focusout", "input[name=url]", function() {
        if($("input[name=url]").val().indexOf("reboot") !== -1) {
            $("label[for=expected]").html("Capture services after reboot? (1: yes; 0: no)");
            $("input[name=expected]").attr("placeholder", "1");
        }
    });
    $("input[name=url]").focusout();
});