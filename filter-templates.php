<?php

require "include/dataconnect.php";


$category   = $_POST['category'] ?? '';
$gametype   = $_POST['gametype'] ?? '';
$duration   = $_POST['duration'] ?? '';
$complexity = $_POST['complexity'] ?? '';

$where = "WHERE gt.gt_status=1";

if($category!=""){
$where .= " AND gt.gt_id IN (SELECT gtc_template_id FROM tb_cms_gt_category_map WHERE gtc_category_id=$category)";
}

if($gametype!=""){
$where .= " AND gt.gt_id IN (SELECT gtg_template_id FROM tb_cms_gt_gametype_map WHERE gtg_gametype_id=$gametype)";
}

if($duration!=""){
$where .= " AND gt.gt_id IN (SELECT gtd_template_id FROM tb_cms_gt_duration_map WHERE gtd_duration_id=$duration)";
}

if($complexity!=""){
$where .= " AND gt.gt_id IN (SELECT gtx_template_id FROM tb_cms_gt_complexity_map WHERE gtx_complexity_id=$complexity)";
}

$sql="SELECT * FROM tb_cms_gametype_template gt $where ORDER BY gt_id DESC";

$result=mysqli_query($conn,$sql);

while($row=mysqli_fetch_assoc($result)){

echo '
<a href="template-details.php?id='.$row['gt_id'].'" class="template-card">

<div class="template-img">
<img src="/trainerByte/portalcms/pages/gt-templates/gt-templates-uploads/'.$row['gt_image'].'"></div>

<div class="template-body">
<h3>'.$row['gt_title'].'</h3>
<p class="tagline">'.$row['gt_tagline'].'</p>
<p class="desc">'.$row['gt_short_desc'].'</p>
</div>

</a>
';

}

