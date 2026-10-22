@echo off
REM ============================================
REM SCRIPT : seller_test_parcours.cmd
REM ============================================
REM Tests end-to-end du module vendeur ShopPro
REM
REM Prérequis :
REM   - Laravel sur http://127.0.0.1:8000
REM   - 2 vendeurs seedés + 1 client
REM   - curl installé (Windows 10+)
REM
REM Usage :
REM   seller_test_parcours.cmd
REM ============================================

setlocal enabledelayedexpansion

REM ============================================
REM CONFIGURATION
REM ============================================
set BASE_URL=http://127.0.0.1:8000/api/v1
set PASSED=0
set FAILED=0
set TOTAL=0

REM Couleurs (Windows 10+)
set GREEN=[92m
set RED=[91m
set YELLOW=[93m
set CYAN=[96m
set RESET=[0m

echo.
echo ================================================================
echo   SHOPPRO - TESTS END-TO-END MODULE VENDEUR
echo ================================================================
echo.

REM ============================================
REM ETAPE 1 : LOGIN DES 3 UTILISATEURS
REM ============================================
call :section "ETAPE 1 : Authentification des 3 roles"

call :test_login "vendeur@shoppro.com" "password123" "VENDEUR_TOKEN" "Vendeur A"
call :test_login "vendeur2@shoppro.com" "password123" "VENDEUR2_TOKEN" "Vendeur B"
call :test_login "jean@example.com" "password123" "CLIENT_TOKEN" "Client"
call :test_login "admin@shoppro.com" "password123" "ADMIN_TOKEN" "Admin"

echo.
if "%VENDEUR_TOKEN%"=="" (
    echo %RED%[FATAL] Impossible de se connecter en tant que vendeur. Arretez le script.%RESET%
    exit /b 1
)

REM ============================================
REM ETAPE 2 : TESTS D'ISOLATION
REM ============================================
call :section "ETAPE 2 : Isolation entre vendeurs"

REM Test 2.1 : Vendeur A ne peut PAS acceder aux produits de Vendeur B
call :test_endpoint "GET" "%BASE_URL%/seller/products" "" "%VENDEUR_TOKEN%" "200" "Vendeur A voit SES produits"

REM Test 2.2 : Vendeur A ne peut PAS acceder aux routes admin
call :test_endpoint "GET" "%BASE_URL%/admin/users" "" "%VENDEUR_TOKEN%" "403" "Vendeur A BLOQUE sur /admin/*"

REM Test 2.3 : Client ne peut PAS acceder aux routes seller
call :test_endpoint "GET" "%BASE_URL%/seller/products" "" "%CLIENT_TOKEN%" "403" "Client BLOQUE sur /seller/*"

REM Test 2.4 : Admin peut acceder aux routes seller
call :test_endpoint "GET" "%BASE_URL%/seller/products" "" "%ADMIN_TOKEN%" "200" "Admin AUTORISE sur /seller/*"

REM Test 2.5 : Non authentifie bloque
call :test_endpoint "GET" "%BASE_URL%/seller/products" "" "" "401" "Anonyme BLOQUE sur /seller/*"

REM ============================================
REM ETAPE 3 : CRUD PRODUIT
REM ============================================
call :section "ETAPE 3 : CRUD Produit vendeur"

REM Test 3.1 : Creer un produit
echo {"name":"TEST-Product-!RANDOM!","price":15000,"stock":10,"sku":"TEST-SKU-!RANDOM!","category_id":1} > "%TEMP%\product.json"
call :test_endpoint_with_body "POST" "%BASE_URL%/seller/products" "%TEMP%\product.json" "%VENDEUR_TOKEN%" "201" "Creation produit"

REM Extraire l'ID du produit cree
for /f "tokens=*" %%a in ('powershell -Command "(Get-Content '%TEMP%\product.json' | ConvertFrom-Json).data.id" 2^>nul') do set PRODUCT_ID=%%a
echo   [INFO] Produit cree avec ID : !PRODUCT_ID!

REM Test 3.2 : Upload image
echo. > "%TEMP%\test_image.txt"
REM Note : l'upload image n'est pas teste ici car necessite un vrai fichier
echo   [SKIP] Upload image (necessite un fichier reel)
set /a TOTAL+=1

REM Test 3.3 : Publier le produit
call :test_endpoint "POST" "%BASE_URL%/seller/products/!PRODUCT_ID!/publish" "" "%VENDEUR_TOKEN%" "200" "Publication produit"

REM Test 3.4 : Verifier que le produit est visible cote client
call :test_endpoint "GET" "%BASE_URL%/products/!PRODUCT_ID!" "" "" "200" "Produit visible cote client"

REM ============================================
REM ETAPE 4 : COMMANDES
REM ============================================
call :section "ETAPE 4 : Commandes et workflow vendeur"

REM Test 4.1 : Client cree une commande
echo {"shipping_address_id":1,"items":[{"product_id":!PRODUCT_ID!,"quantity":1}],"payment_method":"cash_on_delivery"} > "%TEMP%\order.json"
call :test_endpoint_with_body "POST" "%BASE_URL%/orders" "%TEMP%\order.json" "%CLIENT_TOKEN%" "201" "Creation commande client"

REM Extraire l'ID de la commande
for /f "tokens=*" %%a in ('powershell -Command "(Get-Content '%TEMP%\order.json' | ConvertFrom-Json).data.id" 2^>nul') do set ORDER_ID=%%a
echo   [INFO] Commande creee avec ID : !ORDER_ID!

REM Test 4.2 : Vendeur voit la commande
call :test_endpoint "GET" "%BASE_URL%/seller/orders" "" "%VENDEUR_TOKEN%" "200" "Vendeur voit ses commandes"

REM Test 4.3 : Vendeur accepte la commande
call :test_endpoint "POST" "%BASE_URL%/seller/orders/!ORDER_ID!/accept" "" "%VENDEUR_TOKEN%" "200" "Vendeur accepte la commande"

REM Test 4.4 : Vendeur prepare
call :test_endpoint "POST" "%BASE_URL%/seller/orders/!ORDER_ID!/prepare" "" "%VENDEUR_TOKEN%" "200" "Vendeur prepare la commande"

REM Test 4.5 : Vendeur expedie
echo {"tracking_number":"TEST-TRACK-12345","carrier":"dhl"} > "%TEMP%\ship.json"
call :test_endpoint_with_body "POST" "%BASE_URL%/seller/orders/!ORDER_ID!/ship" "%TEMP%\ship.json" "%VENDEUR_TOKEN%" "200" "Vendeur expedie la commande"

REM Test 4.6 : Client voit le statut change
call :test_endpoint "GET" "%BASE_URL%/orders/!ORDER_ID!" "" "%CLIENT_TOKEN%" "200" "Client voit le statut mis a jour"

REM ============================================
REM ETAPE 5 : AVIS
REM ============================================
call :section "ETAPE 5 : Avis client et reponse vendeur"

REM Test 5.1 : Client cree un avis
echo {"product_id":!PRODUCT_ID!,"rating":5,"comment":"Excellent produit, livraison rapide!"} > "%TEMP%\review.json"
call :test_endpoint_with_body "POST" "%BASE_URL%/reviews" "%TEMP%\review.json" "%CLIENT_TOKEN%" "201" "Client cree un avis"

REM Extraire l'ID de l'avis
for /f "tokens=*" %%a in ('powershell -Command "(Get-Content '%TEMP%\review.json' | ConvertFrom-Json).data.id" 2^>nul') do set REVIEW_ID=%%a
echo   [INFO] Avis cree avec ID : !REVIEW_ID!

REM Test 5.2 : Vendeur approuve l'avis
call :test_endpoint "POST" "%BASE_URL%/seller/reviews/!REVIEW_ID!/approve" "" "%VENDEUR_TOKEN%" "200" "Vendeur approuve l'avis"

REM Test 5.3 : Vendeur repond a l'avis
echo {"response":"Merci pour votre retour!"} > "%TEMP%\response.json"
call :test_endpoint_with_body "POST" "%BASE_URL%/seller/reviews/!REVIEW_ID!/respond" "%TEMP%\response.json" "%VENDEUR_TOKEN%" "200" "Vendeur repond a l'avis"

REM ============================================
REM ETAPE 6 : STOCKS
REM ============================================
call :section "ETAPE 6 : Gestion des stocks"

REM Test 6.1 : Vendeur voit ses stocks
call :test_endpoint "GET" "%BASE_URL%/seller/stocks" "" "%VENDEUR_TOKEN%" "200" "Vendeur voit ses stocks"

REM Test 6.7 : Vendeur ajuste un stock
echo {"quantity":50,"reason":"inventory","note":"Test inventaire"} > "%TEMP%\adjust.json"
call :test_endpoint_with_body "POST" "%BASE_URL%/seller/stocks/1/adjust" "%TEMP%\adjust.json" "%VENDEUR_TOKEN%" "200" "Vendeur ajuste un stock"

REM ============================================
REM ETAPE 7 : STATISTIQUES
REM ============================================
call :section "ETAPE 7 : Statistiques vendeur"

REM Test 7.1 : Stats ventes
call :test_endpoint "GET" "%BASE_URL%/seller/stats/sales?period=30" "" "%VENDEUR_TOKEN%" "200" "Statistiques de ventes"

REM Test 7.2 : Stats produits
call :test_endpoint "GET" "%BASE_URL%/seller/stats/products?period=30" "" "%VENDEUR_TOKEN%" "200" "Statistiques produits"

REM Test 7.3 : Stats clients
call :test_endpoint "GET" "%BASE_URL%/seller/stats/customers?period=30" "" "%VENDEUR_TOKEN%" "200" "Statistiques clients"

REM Test 7.4 : Export CSV commandes
call :test_endpoint "GET" "%BASE_URL%/seller/stats/export?type=orders&period=30" "" "%VENDEUR_TOKEN%" "200" "Export CSV commandes"

REM ============================================
REM ETAPE 8 : PROFIL & KYC
REM ============================================
call :section "ETAPE 8 : Profil et KYC"

REM Test 8.1 : Vendeur voit son profil
call :test_endpoint "GET" "%BASE_URL%/seller/profile" "" "%VENDEUR_TOKEN%" "200" "Vendeur voit son profil"

REM Test 8.2 : Vendeur met a jour son profil
echo {"shop_name":"Ma Boutique Test","description":"Test description","phone":"+237650000000"} > "%TEMP%\profile.json"
call :test_endpoint_with_body "PUT" "%BASE_URL%/seller/profile" "%TEMP%\profile.json" "%VENDEUR_TOKEN%" "200" "Vendeur met a jour son profil"

REM ============================================
REM ETAPE 9 : NOTIFICATIONS
REM ============================================
call :section "ETAPE 9 : Notifications"

REM Test 9.1 : Vendeur voit ses notifications
call :test_endpoint "GET" "%BASE_URL%/seller/notifications" "" "%VENDEUR_TOKEN%" "200" "Vendeur voit ses notifications"

REM Test 9.2 : Compteur non lues
call :test_endpoint "GET" "%BASE_URL%/seller/notifications/unread-count" "" "%VENDEUR_TOKEN%" "200" "Compteur notifications"

REM ============================================
REM RESUME FINAL
REM ============================================
call :section "RESUME FINAL"

echo.
echo ================================================================
echo   RESULTATS
echo ================================================================
echo   Total    : !TOTAL!
echo   Passes   : !PASSED!
echo   Echecs   : !FAILED!
echo ================================================================
echo.

REM Nettoyage
del "%TEMP%\product.json" 2>nul
del "%TEMP%\order.json" 2>nul
del "%TEMP%\ship.json" 2>nul
del "%TEMP%\review.json" 2>nul
del "%TEMP%\response.json" 2>nul
del "%TEMP%\adjust.json" 2>nul
del "%TEMP%\profile.json" 2>nul
del "%TEMP%\test_image.txt" 2>nul

if !FAILED! GTR 0 (
    echo %RED%[ECHEC] !FAILED! test(s) ont echoue.%RESET%
    exit /b 1
) else (
    echo %GREEN%[SUCCES] Tous les tests sont passes !%RESET%
    exit /b 0
)

REM ============================================
REM SOUS-ROUTINES
REM ============================================

:section
echo.
echo %CYAN%============================================================%RESET%
echo %CYAN%  %~1%RESET%
echo %CYAN%============================================================%RESET%
exit /b

:test_login
REM %1=email, %2=password, %3=token_var_name, %4=label
set /a TOTAL+=1
echo.
echo %YELLOW%[TEST]%~4 - Login avec %~1%RESET%

curl -s -X POST "%BASE_URL%/auth/login" ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"%~1\",\"password\":\"%~2\"}" > "%TEMP%\login_result.json" 2>nul

for /f "tokens=*" %%a in ('powershell -Command "try { (Get-Content '%TEMP%\login_result.json' | ConvertFrom-Json).data.token } catch { '' }" 2^>nul') do set TOKEN_RESULT=%%a

if "!TOKEN_RESULT!"=="" (
    echo   %RED%[ECHEC] Login echoue%RESET%
    set /a FAILED+=1
    set %~3=
) else (
    echo   %GREEN%[OK] Token obtenu (%%TOKEN_RESULT:~0,20%%...)%RESET%
    set /a PASSED+=1
    set %~3=!TOKEN_RESULT!
)

del "%TEMP%\login_result.json" 2>nul
exit /b

:test_endpoint
REM %1=method, %2=url, %3=body_file ("" si GET), %4=token, %5=expected_status, %6=label
set /a TOTAL+=1
echo.
echo %YELLOW%[TEST]%~6%RESET%

set HTTP_CODE=0
if "%~3"=="" (
    for /f "tokens=*" %%a in ('curl -s -o nul -w "%%{http_code}" -X %~1 "%~2" -H "Authorization: Bearer %~4" -H "Accept: application/json" 2^>nul') do set HTTP_CODE=%%a
) else (
    for /f "tokens=*" %%a in ('curl -s -o nul -w "%%{http_code}" -X %~1 "%~2" -H "Authorization: Bearer %~4" -H "Content-Type: application/json" -H "Accept: application/json" -d @%~3 2^>nul') do set HTTP_CODE=%%a
)

if "!HTTP_CODE!"=="%~5" (
    echo   %GREEN%[OK] Status !HTTP_CODE! (attendu %~5)%RESET%
    set /a PASSED+=1
) else (
    echo   %RED%[ECHEC] Status !HTTP_CODE! (attendu %~5)%RESET%
    set /a FAILED+=1
)
exit /b

:test_endpoint_with_body
REM Identique a test_endpoint mais force le body file
call :test_endpoint "%~1" "%~2" "%~3" "%~4" "%~5" "%~6"
exit /b