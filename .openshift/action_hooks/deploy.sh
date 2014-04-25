#!/usr/bin/env bash
 
GIT_DIR_SAVE=$GIT_DIR ; unset GIT_DIR
GIT_WORK_TREE_SAVE=$GIT_WORK_TREE ; unset GIT_WORK_TREE
 
if [ ! -f "${OPENSHIFT_DATA_DIR}composer/bin/composer" ]; then
  export COMPOSER_HOME="${OPENSHIFT_DATA_DIR}.composer"
  echo $COMPOSER_HOME > ${OPENSHIFT_HOMEDIR}.env/user_vars/COMPOSER_HOME
  echo "Installing composer"
  curl -s https://getcomposer.org/installer | env - PATH="/usr/bin:$PATH" php -- --install-dir=$OPENSHIFT_DATA_DIR >/dev/null
  cd $OPENSHIFT_DATA_DIR
  git clone --quiet git://github.com/composer/composer.git composer
  cd $OPENSHIFT_DATA_DIR/composer
  env - PATH="/usr/bin:$PATH" COMPOSER_HOME="$COMPOSER_HOME" php ${OPENSHIFT_DATA_DIR}composer.phar install >/dev/null

  php ${OPENSHIFT_DATA_DIR}composer.phar create-project typo3/neos-base-distribution TYPO3-Neos
  mkdir -p $OPENSHIFT_DATA_DIR/bin
  ln -s $OPENSHIFT_DATA_DIR/composer/bin/composer $OPENSHIFT_DATA_DIR/bin/composer
else
  echo "Updating composer"
  cd ${OPENSHIFT_DATA_DIR}composer
  git pull --quiet
  php ${OPENSHIFT_DATA_DIR}composer/bin/composer install >/dev/null
fi
 
echo "Running composer install"
cd $OPENSHIFT_REPO_DIR
php $OPENSHIFT_DATA_DIR/bin/composer install
 
export GIT_DIR=$GIT_DIR_SAVE ; unset GIT_DIR_SAVE
export GIT_WORK_TREE=$GIT_WORK_TREE_SAVE ; unset GIT_WORK_TREE