module.exports = function (api) {
  api.cache(true);
  return {
    // babel-preset-expo already injects react-native-worklets/plugin when installed.
    // Do NOT also add react-native-reanimated/plugin — that runs the same plugin twice
    // and can SIGSEGV on Android release when drawer/reanimated mounts.
    presets: ['babel-preset-expo'],
  };
};
