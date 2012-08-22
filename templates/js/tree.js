function linkClickedFunction(event) {
    event.preventDefault();
    target = $(this).attr('href');
    $.History.go(target);
}

$.History.bind(function (state) {
    $('.sidebar-container-right').remove();
    $('#cluetip').remove();
    $('#cluetip-waitimage').remove();

    if (state == '' || state.match('/index.html$') == '/index.html') {
        $('#contentBox').html('<h1>Loading...</h1>').load('index.html' + ' #fileList', function() {
            $('#fileList .fileLink').click(linkClickedFunction);
        });
    } else {
        // Go to specific review
        $('#contentBox').empty().load(encodeURI(state) + ' #review', initReview);
    }
});

$(function() {
    $("#treeToggle").click().toggle(function() {
        $("#tree").animate({width: "hide", opacity: "hide"}, "slow");
        $("#treeToggle").css('background-image', "url('img/treeToggle-collapsed.png')");
    }, function() {
        $("#tree").animate({width: "show", opacity: "show"}, "slow");
        $("#treeToggle").css('background-image', "url('img/treeToggle-extended.png')");
    });

    $("#tree").bind("loaded.jstree", function(event, data) {
        $("#tree").animate({width: "show", opacity: "show"}, "slow");
    }).jstree({
        "plugins" : ["html_data", "themes"]
    });

    $(".treeDir").click(function() {
        $("#tree").jstree("toggle_node", this);
    });

    // When the user clicks on a leaf item in the tree (representing a file)
    // or an item in the fileList, want to hide the filelist/the currently
    // shown review and display the correct review.
    $(".fileLink").click(linkClickedFunction);
});

