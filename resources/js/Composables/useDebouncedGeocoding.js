import axios from 'axios';
import debounce from 'lodash/debounce';
import { onBeforeUnmount, watch } from 'vue';

export const useDebouncedGeocoding = ({
  sourceRef,
  apiKey,
  delay = 1500,
  minLength = 3,
  immediate = false,
  onResolved,
  onError,
}) => {
  const fetchCoordinates = async (address) => {
    const normalizedAddress = String(address ?? '').trim();
    if (!normalizedAddress) {
      return null;
    }

    try {
      const response = await axios.get('https://maps.googleapis.com/maps/api/geocode/json', {
        params: {
          address: normalizedAddress,
          key: apiKey,
        },
      });

      const location = response?.data?.results?.[0]?.geometry?.location;
      if (!location) {
        throw new Error('No geocoding results found');
      }

      onResolved?.(location, normalizedAddress);
      return location;
    } catch (error) {
      onError?.(error, normalizedAddress);
      return null;
    }
  };

  const debouncedFetchCoordinates = debounce((address) => {
    void fetchCoordinates(address);
  }, delay);

  watch(
    sourceRef,
    (newAddress) => {
      const normalizedAddress = String(newAddress ?? '').trim();
      if (normalizedAddress.length >= minLength) {
        debouncedFetchCoordinates(normalizedAddress);
      }
    },
    { immediate },
  );

  onBeforeUnmount(() => {
    debouncedFetchCoordinates.cancel();
  });

  return {
    fetchCoordinates,
    debouncedFetchCoordinates,
  };
};
