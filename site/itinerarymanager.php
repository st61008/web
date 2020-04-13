<?php
// 設定報告等級
error_reporting(E_ERROR | E_WARNING);
/*
在PHP程式檔案提供資料庫相關操作, 
程式是使用Session變數取得資料庫屬性.
*/
// 含括所需的PHP類別宣告
include("classes/guestitinerary.php");
include("classes/flight.php");
include("classes/schedule.php");

$databaseURL;
$databaseUName;
$databasePWord;
$databaseName; 
/*
建立資料庫連接的初始函數, 可以傳回資料庫連接變數.
*/
function initDB(){
    // 從Session變數取得航點資料
    if ( ! isset($_SESSION['databaseURL']) ){
       include("conf/conf.php");
       // 建立AAConf物件取得資料庫連接資訊
       $dbConf = new AAConf();
       $databaseURL = $dbConf->get_databaseURL();
       $databaseUName = $dbConf->get_databaseUName();
       $databasePWord = $dbConf->get_databasePWord();
       $databaseName = $dbConf->get_databaseName();
            
       // 指定資料庫連接資訊的Session變數
       $_SESSION['databaseURL']=$databaseURL; 
       $_SESSION['databaseUName']=$databaseUName; 
       $_SESSION['databasePWord']=$databasePWord; 
       $_SESSION['databaseName']=$databaseName;        
        
       $connection = mysql_connect($databaseURL,$databaseUName,$databasePWord)
                     or die ("錯誤: 連接伺服器錯誤!");
       $db = mysql_select_db($databaseName,$connection)
             or die ("錯誤: 連接資料庫錯誤!"); 
       // 送出utf8編碼與校對      
       mysql_query('SET CHARACTER SET utf8');
       mysql_query("SET collation_connection = 'utf8_general_ci'");
       
       $rowArray;
       $rowID = 1;
       $query = "SELECT * FROM Sectors";
       $result = mysql_query($query);
       while($row = mysql_fetch_array($result)){    
          $rowArray[$rowID] = $row['Sector'];   
          $rowID = $rowID + 1;
       }  
       // 更新航點資訊的Session變數
       $_SESSION['sectors'] = $rowArray;    
       mysql_close($connection);
    }
    $databaseURL = $_SESSION['databaseURL'];
    $databaseUName = $_SESSION['databaseUName'];
    $databasePWord = $_SESSION['databasePWord'];
    $databaseName = $_SESSION['databaseName']; 

    $connection = mysql_connect($databaseURL,$databaseUName,$databasePWord)
                  or die ("錯誤: 連接伺服器錯誤!");
    $db = mysql_select_db($databaseName,$connection)
          or die ("錯誤: 連接資料庫錯誤!"); 
    // 送出utf8編碼與校對      
    mysql_query('SET CHARACTER SET utf8');
    mysql_query("SET collation_connection = 'utf8_general_ci'");
    
    return $connection;
}

/*
關閉資料庫連接
*/
function closeDB($connection){
    mysql_close($connection);
}

/*
函數可以取消訂票
使用訂票編號刪除Itinerary和Schedule資料表的訂票資料,
但是保留客戶資料, 行程編號是從Itinerary資料表取得.
函數傳入訂票編號, 成功傳回 0
*/
function cancelReservation($IID){
    $connection = initDB();
    
    // 查詢訂票編號的行程編號
    $query2 = "SELECT * FROM Itinerary WHERE IID='".$IID."'";
    $result2 = mysql_query($query2);
        //or die ("查詢失敗: ".mysql_error());
    $SID;
    // 取得行程編號
    while($row2 = mysql_fetch_array($result2)){        
            $SID = $row2['SID'];                         
        }
    // 刪除Schedule資料表的記錄資料
    $query2 = "DELETE FROM Schedule WHERE SID='".$SID."'";
    $result2 = mysql_query($query2);
        //or die ("查詢失敗: ".mysql_error()); 
        
    // 刪除Itinerary資料表的記錄資料
    $query2 = "DELETE FROM Itinerary WHERE IID='".$IID."'";
    $result2 = mysql_query($query2);
        //or die ("查詢失敗: ".mysql_error());

    closeDB($connection);
    return 0;
}

/*
函數是用來處理訂票, 在取得客戶輸入旅程資訊後, 
更新資料庫的記錄資料, 在更新後產生訂票編號IID, 
可以用來檢查訂票狀態. 
函數傳入姓, 名, 出發地點(例如: SFO), 
目地地點, 喜愛的航班(例如: AA056)和出發日期
函數傳回訂票編號, 失敗傳回-1.
*/
function processReservation($fname,$lname,$sourcelist,$destlist,$flight,$sdate){
    $connection = initDB();
    $query2;
        
    // 更新Guest資料表
    $query2 = "SELECT * FROM Guest WHERE FirstName='".$fname."' AND LastName='".$lname."'";
    $result2 = mysql_query($query2);
        //or die ("查詢失敗: ".mysql_error());

    $registeredGuest = false;
    $guestID;

    while($row2 = mysql_fetch_array($result2)){        
       $guestID = $row2['GID'];
       $registeredGuest = true;               
    }
    // 客戶編號錯誤, 表示是第一次搭飛機.
    if(! $registeredGuest){        
        // 更新Guest資料表, 取得最後一個客戶編號
        $query2 = "SELECT MAX(GID) FROM Guest";
        $result2 = mysql_query($query2);
                //or die ("查詢失敗: ".mysql_error());
        $row2 = mysql_fetch_array($result2);
        $MGID = $row2[0];  // 取得最後一個客戶編號
        
        $guestID = $MGID + 1;  // 取得新的客戶編號
        
        // 新增客戶資料
        $query2 = "INSERT INTO Guest Values('".$guestID."','".$fname."','".$lname."')";
        $result2 = mysql_query($query2);
                //or die ("查詢失敗: ".mysql_error()); 
    }        
        
    // 取得航班編號
    $query = "SELECT * FROM Flights WHERE FName='".$flight."'";
    $result = mysql_query($query);
        //or die ("查詢失敗: ".mysql_error());
    $row2 = mysql_fetch_array($result);  
    $FID = $row2['FID'];         
        
    // 取得Schedule資料表最後一個行程編號 
    $query2 = "SELECT MAX(SID) FROM Schedule";
    $result2 = mysql_query($query2);
        //or die ("查詢失敗: ".mysql_error());
    $row2 = mysql_fetch_array($result2);
    $MSID = $row2[0];     // 取得最大的行程編號

    $SID = $MSID + 1;     // 取得新的行程編號     
    // 在新增Schedule和Itinerary資料表前, 檢查是否有重複記錄
    $query2 = "SELECT * FROM Schedule WHERE GID='".$guestID."' AND FID='".$FID."' AND Date='".$sdate."'";
    $result2 = mysql_query($query2);
        //or die ("查詢失敗: ".mysql_error());

    $duplicateItinerary = false;
    $guestID;
    // 如果有查詢到記錄, 就表示重複訂票
    while($row2 = mysql_fetch_array($result2)){  
       $duplicateItinerary = true;               
    }

    if($duplicateItinerary){
        // 訂票資料重複, 傳回 -1.
        return -1;
    }   

    // 新增行程資料
    $query2 = "INSERT INTO Schedule Values('".$SID."','".$guestID."','".$FID."','".$sdate."')";
    $result2 = mysql_query($query2);
        //or die ("查詢失敗: ".mysql_error());
        
    // 取得Itinerary資料表的最後一個訂票編號       
    $query2 = "SELECT MAX(IID) FROM Itinerary";
    $result2 = mysql_query($query2);
        // or die ("查詢失敗: ".mysql_error());
    $row2 = mysql_fetch_array($result2);
    $MIID = $row2[0];     // 取得最大的訂票編號 
    
    $IID = $MIID + 1;     // 取得新的訂票編號 
    // 最後新增訂票資料 
    $query2 = "INSERT INTO Itinerary Values('".$IID."','".$guestID."','".$FID."','".$SID."')";
    $result2 = mysql_query($query2);
        //or die ("查詢失敗: ".mysql_error());

    closeDB($connection);
    return $IID;
}

/*
函數可以取得可用的航班資訊
在傳入兩個航點後, 傳回查詢Flights資料表的可用航班
函數傳入出發地點(例如: SFO) 和目的地點
傳回值是可用的航班陣列
*/
function getAvailableFlights($source,$dest){

    $connection = initDB();
    $query2;       
    // 取得出發地點的航點編號SID (請注意! 它和行程編號的欄位名稱相同)
    $query2 = "SELECT * FROM Sectors WHERE Sector='".$source."'";
    $result2 = mysql_query($query2);
        //or die ("查詢失敗: ".mysql_error());                
    $row2 = mysql_fetch_array($result2);
    $SourceSID = $row2['SID'];
    // 取得目的地點的航點編號SID
    $query3 = "SELECT * FROM Sectors WHERE Sector='".$dest."'";
    $result3 = mysql_query($query3);
        // or die ("查詢失敗: ".mysql_error());                
    $row3 = mysql_fetch_array($result3);
    $destSID= $row3['SID'];
        
    // 取得Flights資料表的可用航班
    $query3 = "SELECT * FROM Flights WHERE SourceSID='".$SourceSID."' AND DestSID='".$destSID."'";
    $result3 = mysql_query($query3);
        //  or die ("查詢失敗: ".mysql_error()); 

    $flightsArray;
    $flightsID = 1;
    // 建立可用航班陣列
    while($row = mysql_fetch_array($result3)){        
       $fName= $row['FName'];
       $flightsArray[$flightsID] = $fName;
       $flightsID = $flightsID + 1;
    }
    closeDB($connection);
    return $flightsArray;
}

/*
函數傳回航班資訊
使用航班編號查詢Flight資料表的航班資訊
並且查詢Sectors資料表的航點資訊.
函數傳入航班編號. 如果為0就是取得所有航班資訊
傳回Flight物件陣列的航班資訊,
類別宣告是在classes/子資料夾的flight.php.
*/
function getFlightInfo($FID){
    $connection = initDB();
    $query;
    // 以參數建立SQL指令查詢所有航班或只有指定航班資料
    if( $FID == 0 ){
       $query = "SELECT * FROM Flights";   // 全部             
    }
    else{
       $query = "SELECT * FROM Flights WHERE FID='".$FID."'";               
    }

    $result = mysql_query($query);
        // or die ("查詢失敗: ".mysql_error());

    $flightData;
    $flightID = 0;

    while($row = mysql_fetch_array($result)){   
       $FID = $row['FID'];
       $FName = $row['FName'];
       $SourceSID = $row['SourceSID'];
       $DestSID = $row['DestSID'];
       // 取得出發地點的航點資訊
       $query2 = "SELECT * FROM Sectors WHERE SID='".$SourceSID."'";
       $result2 = mysql_query($query2);
              //or die ("Query Failed ".mysql_error());                
       $row2 = mysql_fetch_array($result2);
       $source = $row2['Sector'];
       // 取得目的地點的航點資訊 
       $query3 = "SELECT * FROM Sectors WHERE SID='".$DestSID."'";
       $result3 = mysql_query($query3);
                //or die ("查詢失敗: ".mysql_error());                
       $row3 = mysql_fetch_array($result3);
       $dest= $row3['Sector'];
                
       // 建立Flight物件
       $flight = new Flight();        
       $flight->set_FID($FID);
       $flight->set_FName($FName);
       $flight->set_source($source);
       $flight->set_dest($dest);
           
       // 建立Flight物件陣列
       $flightData[$flightID] = $flight;
       $flightID = $flightID +1;              
    }
    closeDB($connection);
    return $flightData;
}

/*
函數傳回訂票資料
使用訂票編號查詢Itinerary資料表的訂票資訊
並且查詢Guest資料表的客戶姓名, Schedule行程資料表的日期, 
Flights資料表的名稱, 出發地點和目的地點, 然後就可以
查詢Sectors資料表的航點資訊.
函數傳入訂票編號. 如果為0就是取得所有訂票資訊
傳回GuestItinerary物件陣列的客戶訂票資訊,
類別宣告是在classes/子資料夾的guestitinerary.php.
*/
function getItinerary($IID){
    $connection = initDB();
    $query;
    // 以參數建立SQL指令查詢所有訂票或只有指定訂票資料
    if($IID == 0){
        $query = "SELECT * FROM Itinerary";   // 全部             
    }
    else{
        $query = "SELECT * FROM Itinerary WHERE IID='".$IID."'";               
    }

    $result = mysql_query($query);
        //or die ("查詢失敗: ".mysql_error());
        
    $itineraryID = 0;
    $itineraryData;

    while($row = mysql_fetch_array($result)){   
        $GID = $row['GID'];
        $FID = $row['FID'];
        $SID = $row['SID'];
               
        // 取得客戶姓名
        $query2 = "SELECT * FROM Guest WHERE GID='".$GID."'";
        $result2 = mysql_query($query2);
        $row2 = mysql_fetch_array($result2);
        $firstName = $row2['FirstName'];
        $lastName = $row2['LastName'];
                
        // 取得行程的日期
        $query3 = "SELECT * FROM Schedule WHERE SID='".$SID."'";
        $result3 = mysql_query($query3);
        $row3 = mysql_fetch_array($result3);
        $travelDate = $row3['Date'];
                
        // 取得航班資料的名稱, 出發地點和目的地點
        $query3 = "SELECT * FROM Flights WHERE FID='".$FID."'";
        $result3 = mysql_query($query3);
        $row3 = mysql_fetch_array($result3);
        $sourceSID = $row3['SourceSID'];
        $destSID = $row3['DestSID'];
        $fName = $row3['FName'];
        // 取得出發地點的航點資訊
        $query4 = "SELECT Sector FROM Sectors WHERE SID='".$sourceSID."'";
        $result4 = mysql_query($query4);
        $row4 = mysql_fetch_array($result4);
        $source = $row4['Sector'];
        // 取得目的地點的航點資訊 
        $query4 = "SELECT Sector FROM Sectors WHERE SID='".$destSID."'";
        $result4 = mysql_query($query4);
        $row4 = mysql_fetch_array($result4);
        $dest = $row4['Sector'];
                
        // 建立GuestItinerary物件  
        $guestItinerary = new GuestItinerary();
     
        $guestItinerary->set_FID($FID);
        $guestItinerary->set_FName($fName);
        $guestItinerary->set_SID($SID);
        $guestItinerary->set_source($source);
        $guestItinerary->set_dest($dest);
        $guestItinerary->set_travelDate($travelDate);
      
        $guestItinerary->set_GID($GID);
        $guestItinerary->set_firstName($firstName);
        $guestItinerary->set_lastName($lastName);    
        // 建立GuestItinerary物件陣列
        $itineraryData[$itineraryID]=$guestItinerary;
        $itineraryID = $itineraryID + 1;        
    }

    closeDB($connection);      
    return $itineraryData;
}
?>