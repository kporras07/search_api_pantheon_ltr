Search API Pantheon Learn to Rank
==================================


## Setting up local Solr instance with LTR (using demigod-tools)

- docker-compose stop solr zk zk-ui
- Edit docker-compose.yml to mount a solr folder with the following structure (./solr/solr8/cloud-config-set)
- Update ./solr/solr8/cloud-config-set/solrconfig.xml to contain ltr stuff as in solrconfig-xml.patch
- docker-compose rm solr zk zk-ui
- docker-compose up -d solr zk zk-ui
- docker-compose exec -u 0 solr /bin/bash
- Run the following command to refresh core config: `solr create_collection -c ${PROJECT_NAME} -d /opt/solr-config/solr8/cloud-config-set -force`
- Now, a GET to http://localhost:8983/solr/solr8-sandbox_shard1_replica_n1/schema/model-store should work!!!

Next:
- Post initial models and features!

curl -XPUT 'http://localhost:8983/solr/solr8-sandbox_shard1_replica_n1/schema/feature-store' --data-binary "@./features/my_efi_features.json" -H 'Content-type:application/json'
curl -XPUT 'http://localhost:8983/solr/solr8-sandbox_shard1_replica_n1/schema/model-store' --data-binary "@./models/linear.json" -H 'Content-type:application/json'


https://opensource.com/article/17/11/learning-rank-apache-solr
https://solr.apache.org/docs/6_6_1/solr-ltr/org/apache/solr/ltr/model/LinearModel.html

http://mirror.olnevhost.net/pub/apache/lucene/solr/ref-guide/apache-solr-ref-guide-8.1.pdf
