<?php
/*-------------------------------------------------------
*
*   LiveStreet Engine Social Networking
*   Copyright © 2008 Mzhelskiy Maxim
*
*--------------------------------------------------------
*
*   Official site: www.livestreet.ru
*   Contact e-mail: rus.engine@gmail.com
*
*   GNU General Public License, version 2:
*   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*
---------------------------------------------------------
*/

class Mapper_Talk extends Mapper {	
	public function AddTalk(TalkEntity_Talk $oTalk) {
		$sql = "INSERT INTO ".Config::Get('db.table.talk')." 
			(user_id,
			talk_title,
			talk_text,
			talk_date,
			talk_date_last,
			talk_user_ip			
			)
			VALUES(?d,	?,	?,	?,  ?, ?)
		";			
		if ($iId=$this->oDb->query($sql,$oTalk->getUserId(),$oTalk->getTitle(),$oTalk->getText(),$oTalk->getDate(),$oTalk->getDateLast(),$oTalk->getUserIp())) 
		{
			return $iId;
		}		
		return false;
	}

	public function UpdateTalk(TalkEntity_Talk $oTalk) {
		$sql = "UPDATE ".Config::Get('db.table.talk')." SET			
				talk_date_last = ? ,
				talk_count_comment = ? 
			WHERE 
				talk_id = ?d
		";			
		return $this->oDb->query($sql,$oTalk->getDateLast(),$oTalk->getCountComment(),$oTalk->getId());
	}
	
	public function GetTalksByArrayId($aArrayId) {
		if (!is_array($aArrayId) or count($aArrayId)==0) {
			return array();
		}
				
		$sql = "SELECT 
					t.*							 
				FROM 
					".Config::Get('db.table.talk')." as t 
				WHERE 
					t.talk_id IN(?a) 									
				ORDER BY FIELD(t.talk_id,?a) ";
		$aTalks=array();
		if ($aRows=$this->oDb->select($sql,$aArrayId,$aArrayId)) {
			foreach ($aRows as $aRow) {
				$aTalks[]=new TalkEntity_Talk($aRow);
			}
		}		
		return $aTalks;
	}
	
	public function GetTalkUserByArray($aArrayId,$sUserId) {
		if (!is_array($aArrayId) or count($aArrayId)==0) {
			return array();
		}
				
		$sql = "SELECT 
					t.*							 
				FROM 
					".Config::Get('db.table.talk_user')." as t 
				WHERE 
					t.user_id = ?d 
					AND
					t.talk_id IN(?a) 									
				";
		$aTalkUsers=array();
		if ($aRows=$this->oDb->select($sql,$sUserId,$aArrayId)) {
			foreach ($aRows as $aRow) {
				$aTalkUsers[]=new TalkEntity_TalkUser($aRow);
			}
		}
		return $aTalkUsers;
	}
	
	public function GetTalkById($sId) {		

		$sql = "SELECT 
				t.*,
				u.user_login as user_login							 
				FROM 
					".Config::Get('db.table.talk')." as t,
					".Config::Get('db.table.user')." as u
				WHERE 
					t.talk_id = ?d 					
					AND
					t.user_id=u.user_id					
					";
		
		if ($aRow=$this->oDb->selectRow($sql,$sId)) {
			return new TalkEntity_Talk($aRow);
		}
		return null;
	}
		
		
	public function AddTalkUser(TalkEntity_TalkUser $oTalkUser) {
		$sql = "INSERT INTO ".Config::Get('db.table.talk_user')." 
			(talk_id,
			user_id,
			date_last		
			)
			VALUES(?d,  ?d, ?)
		";			
		if ($this->oDb->query($sql,$oTalkUser->getTalkId(),$oTalkUser->getUserId(),$oTalkUser->getDateLast())===0) 
		{
			return true;
		}		
		return false;
	}
	
	public function UpdateTalkUser(TalkEntity_TalkUser $oTalkUser) {
		//
		// Рефакторинг:
		// переход на систему учета активных\неактивных пользователей в переписке
		/**		
		$sql = "UPDATE ".Config::Get('db.table.talk_user')." 
			SET 
				date_last = ?, 				
				comment_id_last = ?d, 				
				comment_count_new = ?d 				
			WHERE
				talk_id = ?d
				AND
				user_id = ?d
		";	
		**/		
		$sql = "UPDATE ".Config::Get('db.table.talk_user')." 
			SET 
				date_last = ?, 				
				comment_id_last = ?d, 				
				comment_count_new = ?d, 	
				talk_user_active = ?d			
			WHERE
				talk_id = ?d
				AND
				user_id = ?d
		";	
		
		if (
			$this->oDb->query(
				$sql,
				$oTalkUser->getDateLast(),
				$oTalkUser->getCommentIdLast(),
				$oTalkUser->getCommentCountNew(),
				$oTalkUser->getIsActive(),
				$oTalkUser->getTalkId(),
				$oTalkUser->getUserId()
			)
		) {
			return true;
		}		
		return false;
	}
	
	
	public function DeleteTalkUserByArray($aTalkId,$sUserId) {
		if (!is_array($aTalkId)) {
			$aTalkId=array($aTalkId);
		}
		//
		// Рефакторинг:
		// переход на систему учета активных\неактивных пользователей в переписке
		//$sql = "DELETE FROM ".Config::Get('db.table.talk_user')." 
		//	WHERE
		//		talk_id IN (?a)
		//		AND
		//		user_id = ?d				
		//";			
		$sql = "
			UPDATE ".Config::Get('db.table.talk_user')." 
			SET 
				talk_user_active = 0
			WHERE
				talk_id IN (?a)
				AND
				user_id = ?d				
		";		
		if ($this->oDb->query($sql,$aTalkId,$sUserId)) 
		{
			return true;
		}		
		return false;
	}
	
		
		
	public function GetCountCommentNew($sUserId) {
		$sql = "
					SELECT
						SUM(tu.comment_count_new) as count_new												
					FROM   						
  						".Config::Get('db.table.talk_user')." as tu
					WHERE   						
  						tu.user_id = ?d  
  						AND
  						tu.talk_user_active=1							
		";
		if ($aRow=$this->oDb->selectRow($sql,$sUserId)) {
			return $aRow['count_new'];
		}
		return false;
	}
	
	public function GetCountTalkNew($sUserId) {
		$sql = "
					SELECT
						COUNT(tu.talk_id) as count_new												
					FROM   						
  						".Config::Get('db.table.talk_user')." as tu
					WHERE
  						tu.date_last IS NULL
  						AND
  						tu.user_id = ?d  	
  						AND
  						tu.talk_user_active=1						
		";
		if ($aRow=$this->oDb->selectRow($sql,$sUserId)) {
			return $aRow['count_new'];
		}
		return false;
	}
	
	public function GetTalksByUserId($sUserId,&$iCount,$iCurrPage,$iPerPage) {		
		//
		// Рефакторинг:
		// переход на систему учета активных\неактивных пользователей в переписке
		/**		
		$sql = "SELECT 
					tu.talk_id									
				FROM 
					".Config::Get('db.table.talk_user')." as tu, 					
					".Config::Get('db.table.talk')." as t							 
				WHERE 
					tu.user_id = ?d 
					AND
					tu.talk_id=t.talk_id	
				ORDER BY t.talk_date_last desc, t.talk_date desc
				LIMIT ?d, ?d	
					";
		**/
		$sql = "SELECT 
					tu.talk_id									
				FROM 
					".Config::Get('db.table.talk_user')." as tu, 					
					".Config::Get('db.table.talk')." as t							 
				WHERE 
					tu.user_id = ?d 
					AND
					tu.talk_id=t.talk_id
					AND
					tu.talk_user_active = '1'	
				ORDER BY t.talk_date_last desc, t.talk_date desc
				LIMIT ?d, ?d	
					";
		
		$aTalks=array();
		if ($aRows=$this->oDb->selectPage($iCount,$sql,$sUserId,($iCurrPage-1)*$iPerPage, $iPerPage)) {
			foreach ($aRows as $aRow) {
				$aTalks[]=$aRow['talk_id'];
			}
		}
		return $aTalks;
	}	

		
	public function GetUsersTalk($sTalkId) {
		$sql = "
			SELECT 
				user_id	 
			FROM 
				".Config::Get('db.table.talk_user')." 	  
			WHERE
				talk_id = ? 

			";	
		$aReturn=array();
		if ($aRows=$this->oDb->select($sql,$sTalkId)) {
			foreach ($aRows as $aRow) {
				$aReturn[]=$aRow['user_id'];
			}
		}

		return $aReturn;
	}
	
	public function increaseCountCommentNew($sTalkId,$aExcludeId) {
		if (!is_null($aExcludeId) and !is_array($aExcludeId)) {
			$aExcludeId=array($aExcludeId);
		}
		
		$sql = "UPDATE 			  
				".Config::Get('db.table.talk_user')."   
				SET comment_count_new=comment_count_new+1 
			WHERE
				talk_id = ? 
				{ AND user_id NOT IN (?a) }";	
		return $this->oDb->select($sql,$sTalkId,!is_null($aExcludeId) ? $aExcludeId : DBSIMPLE_SKIP);
	}
	
	public function GetTalkUsers($sTalkId) {
		$sql = "
			SELECT 
				t.* 
			FROM 
				".Config::Get('db.table.talk_user')." as t 	  
			WHERE
				talk_id = ? 

			";	
		$aReturn=array();
		if ($aRows=$this->oDb->select($sql,$sTalkId)) {
			foreach ($aRows as $aRow) {
				$aReturn[]=new TalkEntity_TalkUser($aRow);
			}
		}

		return $aReturn;		
	}
}
?>