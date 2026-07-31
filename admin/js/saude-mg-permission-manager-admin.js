jQuery(document).ready(function($) {
    var modal = $("#smpm-permission-modal");
    var btn = $("#smpm_manage_permissions_button");
    var span = $(".smpm-close-button").first();

    btn.on("click", function() {
        modal.show();
    });

    span.on("click", function() {
        modal.hide();
    });

    $(window).on("click", function(event) {
        if ($(event.target).is(modal)) {
            modal.hide();
        }
    });

    // Search functionality
    $(".smpm-search").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        var targetListId = $(this).data("target");
        $("#" + targetListId + " li").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Select/Deselect all functionality
    $(".smpm-select-all").on("click", function() {
        var targetListId = $(this).data("target");
        $("#" + targetListId + " input[type='checkbox']").prop("checked", true);
    });

    $(".smpm-deselect-all").on("click", function() {
        var targetListId = $(this).data("target");
        $("#" + targetListId + " input[type='checkbox']").prop("checked", false);
    });

    // Handle the save button inside the modal
    $("#smpm-save-permissions").on("click", function(e) {
        e.preventDefault();
        // Copy modal data to main form before hiding
        copyModalDataToMainForm();
        modal.hide();
    });

    // Function to copy modal data to main form
    function copyModalDataToMainForm() {
        // Remove any existing cloned inputs to avoid duplicates
        $("#your-profile .smpm-cloned-input").remove();
        
        // Copy checked checkboxes from modal to main form
        $("#smpm-permission-form input[type='checkbox']:checked").each(function() {
            var clonedInput = $(this).clone();
            clonedInput.addClass('smpm-cloned-input');
            clonedInput.css('display', 'none');
            $("#your-profile").append(clonedInput);
        });
    }

    // Ensure data is copied when main form is submitted
    $("#your-profile").on("submit", function() {
        copyModalDataToMainForm();
    });
});
