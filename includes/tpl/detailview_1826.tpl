<h1>{NAME}</h1>
<div id="{DB}_1">Loading...</div>
<script defer>
 jQuery.ajax({
        url : '{SCRIPT}?db={DB}&{QSTRING}&filterdocs=810,812,830,834,850,855,856,860,870',
        type: 'GET',
        success: function(data){
            jQuery('#{DB}_1').html(data);
        }
    });
</script>
<div id="{DB}_2">Loading...</div>
<script defer>
 jQuery.ajax({
        url : '{SCRIPT}?db={DB}&{QSTRING}&filterdocs=832,867',
        type: 'GET',
        success: function(data){
            jQuery('#{DB}_2').html(data);
        }
    });
</script>

