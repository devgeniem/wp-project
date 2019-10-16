![geniem-github-banner](https://cloud.githubusercontent.com/assets/5691777/14319886/9ae46166-fc1b-11e5-9630-d60aa3dc4f9e.png)
# Google Cloud Build continuous delivery templates

Contents of this folder enable building, testing and deploying the project to Kontena, using Google Cloud Build.

This should work as-is for a basic Geniem WP-project (assuming `PROJECTNAME` and `THEMENAME` have been replaced by `gdev`).
If your project is more complex, feel free to modify the build steps.

## Pipeline
1. Listen to trigger from Github
2. Run tests
3. Build Docker image and push it to registry
4. Trigger Kontena stack upgrade
5. TODO: Notify of results

## Secrets
Secret information in the files is encrypted.
This is done using the `gcloud` command line tools and secret keys saved to Google cloud.
Don't commit plaintext secrets if you modify these.

Stage and production are different projects in Google cloud and thus have different keys.

## Files
- `cloudbuild_stage.yaml` and `cloudbuild_production.yaml` describe the build steps for GCB.
If you change one, change the other. PHPCS section is commented -- enable if the project passes.

- `id_rsa_stage.enc` and `id_rsa_production.enc` are (encrypted) private keys that allow GCB to fetch private repos from Github.
Replace them with new (`gcloud`) encrypted files if you use another cloud.

- `known_hosts` is there to be copied in the GCB known hosts, to allow access to Github.

- `docker-compose-gcloud.yml` describes the Docker compose enviroment that allows running integration tests in GCB.
This should be kept close to the compose file in `../docker-compose.yml`, but the networking is different since we don't have the gdev enviroment in GCB.
`WP_ENV` is "testing" always.
