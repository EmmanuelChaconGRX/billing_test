<h1>{NAME}</h1>
<div id="{DB}">Loading...</div>
<script defer>
 jQuery.ajax({
        url : '{SCRIPT}?db={DB}&{QSTRING}',
        type: 'GET',
        success: function(data){
            jQuery('#{DB}').html(data);
        }
    });
</script>

