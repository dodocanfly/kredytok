```
git clone https://github.com/dodocanfly/kredytok.git

cd kredytok

composer install

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
# confirm with "yes"
php bin/console lexik:jwt:generate-keypair
symfony serve
```

dostępni użytkownicy:
```
testuser1 / testpass1
testuser2 / testpass2
```

generowanie tokenu
```
curl --location 'https://127.0.0.1:8000/api/login_check' \
--header 'Content-Type: application/json' \
--data '{
"username": "testuser1",
"password": "testpass1"
}'
```

dodawanie kalkulacji kredytu
```
curl --location 'https://127.0.0.1:8000/api/loan/calculate' \
--header 'Content-Type: application/json' \
--header 'Authorization: Bearer paste-token-from-above-request-response' \
--data '{
"amount": 11000,
"instalments": 9
}'
```

wykluczanie pojedynczej kalkulacji
```
curl --location --request PUT 'https://127.0.0.1:8000/api/loan/deactivate/2' \
--header 'Authorization: Bearer paste-token-from-above-request-response'
```

listowanie kalkulacji
```
curl --location 'https://127.0.0.1:8000/api/loan/list' \
--header 'Authorization: Bearer paste-token-from-above-request-response'
```
```
curl --location 'https://127.0.0.1:8000/api/loan/list?inactiveOnly=1' \
--header 'Authorization: Bearer paste-token-from-above-request-response'
```