<!DOCTYPE html>
<?php
     include("itinerarymanager.php");
?>
<html>
    <head>
        <meta charset="utf-8" />
        <title>顯示航班行程</title>
        <meta name="keywords" content="schedule, list" />
        <meta name="description" content="This page provides a list of all schedule" />
        <link href="css/default.css" rel="stylesheet" type="text/css" />
    </head>    
    <body>
        <div id="wrapper">
        <?php include 'include/header.php'; ?>
            <!-- end div#header -->
            <div id="page">
                <div id="content">
                    <div id="welcome">
                        <h1>顯示航班行程</h1>
                        <p>
                            在下列表格顯示航班行程, 這是所有會飛的航班清單.
                        </p>
                        <!-- Fetch Rows -->
                        <table class="aatable">
                            <tr>
                                <th>出發日期</th>
                                <th>航班名稱</th>
                                <th>出發地點</th>
                                <th>目的地點</th>
                            </tr>
                            <?php
                            $itineraryData = getitinerary(0);
                            
                            for($index=0;$index < count($itineraryData);$index++){
                                $guestitinerary = $itineraryData[$index];
                                echo "<tr>";
                                echo "<td>".$guestitinerary->get_travelDate()."</td>";
                                echo "<td><a class='data' href='flightinfo.php?FID=".$guestitinerary->get_FID()."'>".$guestitinerary->get_FName()."</a></td>";
                            
                                echo "<td>".$guestitinerary->get_source()."</td>";
                                echo "<td>".$guestitinerary->get_dest()."</td>";                                
                                echo "</tr>";
                            }
                            ?>
                        </table>
                    </div>
                    <div id="note">
                        <p>按一下航班名稱可以顯示進一步資訊</p>
                    </div>
                    <!-- end div#welcome -->                    
                </div>
                <!-- end div#content -->
                <div id="sidebar">
                    <ul>
                    <?php include 'include/nav.php'; ?>
                        <!-- end navigation -->
                        <?php include 'include/updates.php'; ?>
                        <!-- end updates -->
                    </ul>
                </div>
                <!-- end div#sidebar -->
                <div style="clear: both; height: 1px"></div>
            </div>
            <?php include 'include/footer.php'; ?>
        </div>
        <!-- end div#wrapper -->
    </body>
</html>
