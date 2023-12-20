workDir=$(realpath "$(dirname "$0")");
(
  cd "$workDir"/voicekit-examples/golang || exit;
  buildScriptPath='build.sh';
  touch "$buildScriptPath";
  {
    echo 'apt-get update; apt-get install -y libopus-dev libopusfile-dev;';
    # echo 'go get github.com/golang-jwt/jwt/v4; go build get-jwt.go;';
    echo 'go build cmd/recognize/recognize.go;';
    echo 'go build cmd/recognize_stream/recognize_stream.go;';
    echo 'go build cmd/synthesize_stream/synthesize_stream.go;';
    echo 'find / -name "libopusfile.so.0" -exec cp {} ./ \;';
  } >> "$buildScriptPath"
  docker run --rm -v "$PWD":/usr/src/myapp -w /usr/src/myapp golang:bullseye sh "$buildScriptPath";
  rm -rf "$buildScriptPath";

  mv recognize ../../bin/recognize;
  mv recognize_stream ../../bin/recognize_stream;
  mv synthesize_stream ../../bin/synthesize_stream;
  mv libopusfile.so.0 ../../bin/libopusfile.so.0;
  # smv get-jwt ../../bin/get-jwt;
)