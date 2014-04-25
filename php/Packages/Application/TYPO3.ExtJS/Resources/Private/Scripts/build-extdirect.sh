#!/bin/sh

SRC_PATH=../../Public/JavaScript
TARGET_FILENAME=${SRC_PATH}/ext-direct-debug.js
COMPRESSED_FILENAME=${SRC_PATH}/ext-direct.js

# Remove previous version
rm -f ${TARGET_FILENAME}

# Use source files from ext-3.4.1 bundle and move files from root / src and src/ext-core/src

cat ${SRC_PATH}/adapter/ext/ext-base-debug.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/core/Ext-more.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/util/Observable.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/util/DelayedTask.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/core/Element.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/core/Element.style.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/core/EventManager.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/util/JSON.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/direct/Direct.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/direct/Provider.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/direct/JsonProvider.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/direct/RemotingProvider.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/direct/Transaction.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/direct/Event.js >> ${TARGET_FILENAME}
cat ${SRC_PATH}/data/Connection.js >> ${TARGET_FILENAME}

uglifyjs ${TARGET_FILENAME} > ${COMPRESSED_FILENAME}
