package main
import (
  "crypto/rsa"
  "io/ioutil"
  "time"
  "github.com/golang-jwt/jwt/v4"
  "fmt"

  "flag"
)

// Формирование JWT.
func main() {
  var keyID string
  var serviceAccountID string
  var keyFile string

  flag.StringVar(&keyID, "key-id", "", "Open key ID")
  flag.StringVar(&serviceAccountID,  "service-account-id", "", "Service account ID")
  flag.StringVar(&keyFile,  "perm-key-file-path", "", "File perm path")
  flag.Parse()


  claims := jwt.RegisteredClaims{
          Issuer:    serviceAccountID,
          ExpiresAt: jwt.NewNumericDate(time.Now().UTC().Add(1 * time.Hour)),
          IssuedAt:  jwt.NewNumericDate(time.Now().UTC()),
          NotBefore: jwt.NewNumericDate(time.Now().UTC()),
          Audience:  []string{"https://iam.api.cloud.yandex.net/iam/v1/tokens"},
  }
  token := jwt.NewWithClaims(jwt.SigningMethodPS256, claims)
  token.Header["kid"] = keyID

  privateKey := loadPrivateKey(keyFile)
  signed, err := token.SignedString(privateKey)
  if err != nil {
      panic(err)
  }
  fmt.Print(signed, "\n")
}

func loadPrivateKey(keyFile string) *rsa.PrivateKey {
  data, err := ioutil.ReadFile(keyFile)
  if err != nil {
      panic(err)
  }
  rsaPrivateKey, err := jwt.ParseRSAPrivateKeyFromPEM(data)
  if err != nil {
      panic(err)
  }
  return rsaPrivateKey
}