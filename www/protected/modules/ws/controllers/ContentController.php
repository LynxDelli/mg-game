<?php
class ContentController extends CController
{
    /**
     * @return array
     */
    public function actions()
    {
        return array(
            'wsdl' => array(
                'class' => 'CWebServiceAction',
                'classMap' => array(
                    'Status' => 'Status',
                    'StatusCode' => 'StatusCode',
                    'RegisterResult' => 'RegisterResult',
                    'LicenceDTO' => 'LicenceDTO',
                    'CollectionDTO' => 'CollectionDTO',
                    'MediaDTO' => 'MediaDTO',
                    'AssignMediaDTO' => 'AssignMediaDTO'
                ),
            ),
        );
    }


    /**
     * @param InstitutionDTO $institutionDto
     * @return RegisterResult
     * @soap
     */
    public function register($institutionDto)
    {
        $user = new InstallConfigurationForm();
        $transaction = $user->dbConnection->beginTransaction();
        $message = "";
        try {
            $user->username = $institutionDto->username;
            $user->email = $institutionDto->email;
            $user->password = $institutionDto->password;
            $user->activekey = UserModule::encrypting(microtime() . $user->password);
            $user->verifyPassword = $user->password = UserModule::encrypting($user->password);
            $user->created = date('Y-m-d H:i:s');
            $user->modified = date('Y-m-d H:i:s');
            $user->lastvisit = NULL;
            $user->role = 'institution';
            $user->status = User::STATUS_NOACTIVE;

            if ($user->save()) {
                $profile = new Profile;
                $profile->user_id = $user->id;
                $profile->save();

                $institution = new Institution();
                $institution->name = $institutionDto->name;
                $institution->url = $institutionDto->url;
                $institution->logo_url = $institutionDto->logoUrl;
                $institution->description = $institutionDto->description;
                $institution->status = Institution::STATUS_NOACTIVE;
                $institution->user_id = $user->id;
                $institution->token = md5($institution->name . "_" . $institution->url);
                $institution->ip = $institutionDto->ip;
                $institution->website = $institutionDto->website;
                $institution->created = date('Y-m-d H:i:s');

                if ($institution->save()) {
                    $transaction->commit();
                    $res = new RegisterResult();
                    $res->token = $institution->token;
                    $res->status = Status::getStatus(StatusCode::SUCCESS(), "");
                    return $res;
                }

                $errors = $institution->getErrors();
                foreach ($errors as $field => $error) {
                    $message .= $error[0] . ";";
                }
            } else {
                $errors = $user->getErrors();
                foreach ($errors as $field => $error) {
                    $message .= $error[0] . ";";
                }
            }

            $transaction->rollback();
            $rr = new RegisterResult();
            $rr->status = Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), $message);
            return $rr;
        } catch (Exception $ex) {
            $transaction->rollback();
            $res = new RegisterResult();
            $res->status = Status::getStatus(StatusCode::FATAL_ERROR(), $ex->getMessage());
            return $res;
        }
    }

    /**
     * @param InstitutionDTO $institutionDto
     * @return RegisterResult
     * @soap
     */
    public function updateProfile($institutionDto)
    {
        $message = "";
        try {
            $institution = Institution::model()->find('token=:token', array(':token' => $institutionDto->token));
            if ($institution) {
                $institution->name = $institutionDto->name;
                $institution->url = $institutionDto->url;
                $institution->logo_url = $institutionDto->logoUrl;
                $institution->description = $institutionDto->description;
                $institution->ip = $institutionDto->ip;
                $institution->website = $institutionDto->website;
                $institution->token = md5($institution->name . "_" . $institution->url);
                if ($institution->save()) {
                    $res = new RegisterResult();
                    $res->token = $institution->token;
                    $res->status = Status::getStatus(StatusCode::SUCCESS(), "");
                    return $res;
                }

                $errors = $institution->getErrors();
                foreach ($errors as $field => $error) {
                    $message .= $error[0] . ";";
                }

                $rr = new RegisterResult();
                $rr->status = Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), $message);
                return $rr;
            } else {
                $res = new RegisterResult();
                $res->status = Status::getStatus(StatusCode::FATAL_ERROR(), "Invalid token!");
                return $res;
            }
        } catch (Exception $ex) {
            $res = new RegisterResult();
            $res->status = Status::getStatus(StatusCode::FATAL_ERROR(), $ex->getMessage());
            return $res;
        }
    }


    /**
     * @param string $token
     * @param CollectionDTO $collection
     * @return Status
     * @soap
     */
    public function createCollection($token, $collection)
    {
        try {
            $institution = $this->getInstitution($token);
            if ($institution == null) {
                return Status::getStatus(StatusCode::LOGON_ERROR(), "Invalid token!");
            }

            if (!($collection->id > 0)) {
                return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), "Please set collection id!");
            }

            $lModel = Licence::model()->find('id = 1');

            if ($collection->licenceID > 0) {
                $tmp = Licence::model()->find('institution_id =:instID AND remote_id=:remoteID', array(':instID' => $institution->id, ':remoteID' => $collection->licenceID));
                if ($tmp != null) {
                    $lModel = $tmp;
                }
            }

            $model = new Collection();
            $model->name = $collection->name;
            $model->locked = $collection->locked;
            $model->more_information = $collection->moreInfo;
            $model->licence_id = $lModel->id;
            $model->last_access_interval = $collection->lastAccessInterval;
            $model->remote_id = $collection->id;
            $model->institution_id = $institution->id;
            $model->created = date('Y-m-d H:i:s');
            $model->modified = date('Y-m-d H:i:s');
            $model->ip_restrict = $collection->ipRestrict;

            if ($model->save()) {
                return Status::getStatus(StatusCode::SUCCESS(), "");
            }


            $errors = $model->getErrors();
            $message = "";
            foreach ($errors as $field => $error) {
                $message .= $error[0] . ";";
            }

            return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), $message);
        } catch (Exception $ex) {
            return Status::getStatus(StatusCode::FATAL_ERROR(), $ex->getMessage());
        }

    }

    /**
     * @param string $token
     * @param CollectionDTO $collection
     * @return Status
     * @soap
     */
    public function updateCollection($token, $collection)
    {
        try {
            $institution = $this->getInstitution($token);
            if ($institution == null) {
                return Status::getStatus(StatusCode::LOGON_ERROR(), "Invalid token!");
            }

            $model = Collection::model()->find('institution_id =:instID AND remote_id=:remoteID', array(':instID' => $institution->id, ':remoteID' => $collection->id));
            if ($model == null) {
                return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), "Can not find collection for id " . $collection->id);
            }

            $lModel = Licence::model()->find('id = 1');

            if ($collection->licenceID > 0) {
                $tmp = Licence::model()->find('institution_id =:instID AND remote_id=:remoteID', array(':instID' => $institution->id, ':remoteID' => $collection->licenceID));
                if ($tmp != null) {
                    $lModel = $tmp;
                }
            }

            $model->name = $collection->name;
            $model->locked = $collection->locked;
            $model->more_information = $collection->moreInfo;
            $model->licence_id = $lModel->id;
            $model->last_access_interval = $collection->lastAccessInterval;
            $model->modified = date('Y-m-d H:i:s');
            $model->ip_restrict = $collection->ipRestrict;

            if ($model->save()) {
                return Status::getStatus(StatusCode::SUCCESS(), "");
            }

            $errors = $model->getErrors();
            $message = "";
            foreach ($errors as $field => $error) {
                $message .= $error[0] . ";";
            }
            return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), $message);
        } catch (Exception $ex) {
            return Status::getStatus(StatusCode::FATAL_ERROR(), $ex->getMessage());
        }

    }

    /**
     * @param string $token
     * @param integer $id
     * @return Status
     * @soap
     */
    public function deleteCollection($token, $id)
    {
        try {
            $institution = $this->getInstitution($token);
            if ($institution == null) {
                return Status::getStatus(StatusCode::LOGON_ERROR(), "Invalid token!");
            }

            $model = Collection::model()->find('institution_id =:instID AND remote_id=:remoteID', array(':instID' => $institution->id, ':remoteID' => $id));
            if ($model == null) {
                return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), "Can not find collection for id " . $id);
            }

            if ($model->hasAttribute("locked") && $model->locked) {
                return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), "Collection locked!");
            } elseif ($model->delete()) {
                return Status::getStatus(StatusCode::SUCCESS(), "");
            }

            $errors = $model->getErrors();
            $message = "";
            foreach ($errors as $field => $error) {
                $message .= $error[0] . ";";
            }

            return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), $message);
        } catch (Exception $ex) {
            return Status::getStatus(StatusCode::FATAL_ERROR(), $ex->getMessage());
        }
    }

    /**
     * @param string $token
     * @param LicenceDTO $licence
     * @return Status
     * @soap
     */
    public function createLicence($token, $licence)
    {
        try {
            $institution = $this->getInstitution($token);
            if ($institution == null) {
                return Status::getStatus(StatusCode::LOGON_ERROR(), "Invalid token!");
            }

            $model = new Licence();
            $model->name = $licence->name;
            $model->description = $licence->description;
            $model->remote_id = $licence->id;
            $model->institution_id = $institution->id;
            $model->created = date('Y-m-d H:i:s');
            $model->modified = date('Y-m-d H:i:s');

            if ($model->save()) {
                return Status::getStatus(StatusCode::SUCCESS(), "");
            }


            $errors = $model->getErrors();
            $message = "";
            foreach ($errors as $field => $error) {
                $message .= $error[0] . ";";
            }

            return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), $message);
        } catch (Exception $ex) {
            return Status::getStatus(StatusCode::FATAL_ERROR(), $ex->getMessage());
        }
    }

    /**
     * @param string $token
     * @param LicenceDTO $licence
     * @return Status
     * @soap
     */
    public function updateLicence($token, $licence)
    {
        try {
            $institution = $this->getInstitution($token);
            if ($institution == null) {
                return Status::getStatus(StatusCode::LOGON_ERROR(), "Invalid token!");
            }

            $model = Licence::model()->find('institution_id =:instID AND remote_id=:remoteID', array(':instID' => $institution->id, ':remoteID' => $licence->id));
            if ($model == null) {
                return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), "Can not find licence for id " . $licence->id);
            }
            $model->name = $licence->name;
            $model->description = $licence->description;
            $model->modified = date('Y-m-d H:i:s');

            if ($model->save()) {
                return Status::getStatus(StatusCode::SUCCESS(), "");
            }

            $errors = $model->getErrors();
            $message = "";
            foreach ($errors as $field => $error) {
                $message .= $error[0] . ";";
            }

            return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), $message);
        } catch (Exception $ex) {
            return Status::getStatus(StatusCode::FATAL_ERROR(), $ex->getMessage());
        }
    }

    /**
     * @param string $token
     * @param integer $id
     * @return Status
     * @soap
     */
    public function deleteLicence($token, $id)
    {
        try {
            $institution = $this->getInstitution($token);
            if ($institution == null) {
                return Status::getStatus(StatusCode::LOGON_ERROR(), "Invalid token!");
            }

            $model = Licence::model()->find('institution_id =:instID AND remote_id=:remoteID', array(':instID' => $institution->id, ':remoteID' => $id));
            if ($model == null) {
                return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), "Can not find licence for id " . $id);
            }

            if ($model->delete()) {
                return Status::getStatus(StatusCode::SUCCESS(), "");
            }

            $errors = $model->getErrors();
            $message = "";
            foreach ($errors as $field => $error) {
                $message .= $error[0] . ";";
            }

            return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), $message);
        } catch (Exception $ex) {
            return Status::getStatus(StatusCode::FATAL_ERROR(), $ex->getMessage());
        }
    }


    /**
     * @param string $token
     * @param MediaDTO $media
     * @return Status
     * @soap
     */
    public function createMedia($token, $media)
    {
        try {
            $institution = $this->getInstitution($token);
            if ($institution == null) {
                return Status::getStatus(StatusCode::LOGON_ERROR(), "Invalid token!");
            }

            $model = new Media();
            $model->name = $media->name;
            $model->size = $media->size;
            $model->batch_id = $media->batchId;
            $model->mime_type = $media->mimeType;
            $model->locked = $media->locked;
            $model->remote_id = $media->id;
            $model->institution_id = $institution->id;
            $model->created = date('Y-m-d H:i:s');
            $model->modified = date('Y-m-d H:i:s');

            $relatedData = array(
                'collections' => array(1),
            );

            if ($model->saveWithRelated($relatedData)) {
                return Status::getStatus(StatusCode::SUCCESS(), "");
            }

            $errors = $model->getErrors();
            $message = "";
            foreach ($errors as $field => $error) {
                $message .= $error[0] . ";";
            }

            return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), $message);
        } catch (Exception $ex) {
            return Status::getStatus(StatusCode::FATAL_ERROR(), $ex->getMessage());
        }
    }

    /**
     * @param string $token
     * @param integer $id
     * @return Status
     * @soap
     */
    public function deleteMedia($token, $id)
    {
        try {
            $institution = $this->getInstitution($token);
            if ($institution == null) {
                return Status::getStatus(StatusCode::LOGON_ERROR(), "Invalid token!");
            }

            $model = Media::model()->find('institution_id =:instID AND remote_id=:remoteID', array(':instID' => $institution->id, ':remoteID' => $id));
            if ($model == null) {
                return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), "Can not find media for id " . $id);
            }

            if ($model->hasAttribute("locked") && $model->locked) {
                return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), "Media locked!");
            } elseif ($model->delete()) {
                return Status::getStatus(StatusCode::SUCCESS(), "");
            }

            $errors = $model->getErrors();
            $message = "";
            foreach ($errors as $field => $error) {
                $message .= $error[0] . ";";
            }

            return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), $message);
        } catch (Exception $ex) {
            return Status::getStatus(StatusCode::FATAL_ERROR(), $ex->getMessage());
        }
    }

    /**
     * @param string $token
     * @param AssignMediaDTO $assign
     * @return Status
     * @soap
     */
    public function assignMediaToCollections($token, $assign)
    {
        try {
            $institution = $this->getInstitution($token);
            if ($institution == null) {
                return Status::getStatus(StatusCode::LOGON_ERROR(), "Invalid token!");
            }

            $model = Media::model()->find('institution_id =:instID AND remote_id=:remoteID', array(':instID' => $institution->id, ':remoteID' => $assign->id));
            if ($model == null) {
                return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), "Can not find media for id " . $assign->id);
            }
            $ids = implode(",", $assign->collections);
            $cModels = Collection::model()->findAll('institution_id =:instID AND remote_id IN(' . $ids . ')', array(':instID' => $institution->id));

            $mediaCollections = array();
            array_push($mediaCollections, 1);
            foreach ($cModels as $c) {
                array_push($mediaCollections, $c->id);
            }

            $relatedData = array(
                'collections' => $mediaCollections,
            );

            if ($model->saveWithRelated($relatedData)) {
                return Status::getStatus(StatusCode::SUCCESS(), "");
            }

            $errors = $model->getErrors();
            $message = "";
            foreach ($errors as $field => $error) {
                $message .= $error[0] . ";";
            }

            return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), $message);
        } catch (Exception $ex) {
            return Status::getStatus(StatusCode::FATAL_ERROR(), $ex->getMessage());
        }

    }

    /**
     * @param string $token
     * @param AssignMediaDTO[] $assigns
     * @return Status
     * @soap
     */
    public function assignMediasToCollections($token, $assigns)
    {
        try {
            $institution = $this->getInstitution($token);
            if ($institution == null) {
                return Status::getStatus(StatusCode::LOGON_ERROR(), "Invalid token!");
            }

            $message = "";

            foreach ($assigns as $assign) {
                $model = Media::model()->find('institution_id =:instID AND remote_id=:remoteID', array(':instID' => $institution->id, ':remoteID' => $assign->id));
                if ($model == null) {
                    return Status::getStatus(StatusCode::ILLEGAL_ARGUMENT(), "Can not find media for id " . $assign->id);
                }

                $ids = implode(",", $assign->collections);
                $cModels = Collection::model()->findAll('institution_id =:instID AND remote_id IN(' . $ids . ')', array(':instID' => $institution->id));

                $mediaCollections = array();
                array_push($mediaCollections, 1);
                foreach ($cModels as $c) {
                    array_push($mediaCollections, $c->id);
                }

                $relatedData = array(
                    'collections' => $mediaCollections,
                );

                if (!$model->saveWithRelated($relatedData)) {
                    $errors = $model->getErrors();
                    foreach ($errors as $field => $error) {
                        $message .= $error[0] . ";";
                    }
                }
            }
            if (empty($message)) {
                return Status::getStatus(StatusCode::SUCCESS(), $message);
            } else {
                return Status::getStatus(StatusCode::SUCCESS_WITH_WARNINGS(), $message);
            }
        } catch (Exception $ex) {
            return Status::getStatus(StatusCode::FATAL_ERROR(), $ex->getMessage());
        }
    }

    /**
     * @param string $token
     * @return Institution
     */
    private function getInstitution($token)
    {
        $institution = Institution::model()->find('token =:token', array(':token' => $token));
        return $institution;
    }
}
