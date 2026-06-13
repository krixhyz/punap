import { useState, useEffect } from 'react';
import {
    View, Text, TextInput, FlatList, TouchableOpacity, ScrollView, ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useProducts, useSearchSuggestions, useCategories, type ProductFilters } from '../../src/api/products';
import { ProductCard } from '../../src/components/ProductCard';
import { EmptyState } from '../../src/components/EmptyState';
import { ErrorBoundary } from '../../src/components/ErrorBoundary';

const CONDITIONS = ['NEW', 'LIKE_NEW', 'GOOD', 'FAIR', 'POOR'];
const TYPES = ['BUY', 'RENT', 'SWAP'];

function SearchContent() {
    const [query, setQuery] = useState('');
    const [debouncedQ, setDebouncedQ] = useState('');
    const [selectedType, setSelectedType] = useState<string | undefined>();
    const [selectedCondition, setSelectedCondition] = useState<string | undefined>();
    const [minPrice, setMinPrice] = useState('');
    const [maxPrice, setMaxPrice] = useState('');
    const [showSuggestions, setShowSuggestions] = useState(false);

    useEffect(() => {
        const t = setTimeout(() => setDebouncedQ(query), 300);
        return () => clearTimeout(t);
    }, [query]);

    const filters: ProductFilters = {
        q: debouncedQ || undefined,
        transactionType: selectedType,
        condition: selectedCondition,
        minPrice: minPrice ? Number(minPrice) : undefined,
        maxPrice: maxPrice ? Number(maxPrice) : undefined,
        limit: 30,
    };

    const { data, isLoading } = useProducts(filters);
    const { data: suggestions } = useSearchSuggestions(query);
    const { data: categories } = useCategories();
    const products = data?.data ?? [];

    return (
        <SafeAreaView className="flex-1 bg-surface" edges={['top']}>
            {/* Search bar */}
            <View className="px-4 pt-3 pb-2">
                <View className="flex-row items-center bg-card rounded-xl px-4 border border-gray-200">
                    <Text className="text-gray-400 mr-2">🔍</Text>
                    <TextInput
                        className="flex-1 py-3 text-text-primary"
                        placeholder="Search products..."
                        value={query}
                        onChangeText={(v) => { setQuery(v); setShowSuggestions(true); }}
                        onBlur={() => setTimeout(() => setShowSuggestions(false), 150)}
                        returnKeyType="search"
                    />
                    {query ? (
                        <TouchableOpacity onPress={() => { setQuery(''); setDebouncedQ(''); }}>
                            <Text className="text-text-muted text-lg">✕</Text>
                        </TouchableOpacity>
                    ) : null}
                </View>

                {/* Suggestions */}
                {showSuggestions && suggestions && suggestions.length > 0 ? (
                    <View className="bg-card border border-gray-200 rounded-xl mt-1 overflow-hidden absolute top-16 left-4 right-4 z-10 shadow-lg">
                        {suggestions.slice(0, 6).map((s) => (
                            <TouchableOpacity
                                key={s}
                                className="px-4 py-3 border-b border-gray-100"
                                onPress={() => { setQuery(s); setShowSuggestions(false); }}
                            >
                                <Text className="text-text-primary text-sm">{s}</Text>
                            </TouchableOpacity>
                        ))}
                    </View>
                ) : null}
            </View>

            {/* Filters row */}
            <ScrollView horizontal showsHorizontalScrollIndicator={false} className="px-4 mb-2" contentContainerStyle={{ gap: 8 }}>
                {TYPES.map((type) => (
                    <TouchableOpacity
                        key={type}
                        onPress={() => setSelectedType(selectedType === type ? undefined : type)}
                        className="rounded-full px-3 py-1.5"
                        style={{ backgroundColor: selectedType === type ? '#1A6B3C' : '#F3F4F6' }}
                    >
                        <Text style={{ color: selectedType === type ? '#fff' : '#6B7280', fontSize: 12 }}>{type}</Text>
                    </TouchableOpacity>
                ))}
                {CONDITIONS.map((c) => (
                    <TouchableOpacity
                        key={c}
                        onPress={() => setSelectedCondition(selectedCondition === c ? undefined : c)}
                        className="rounded-full px-3 py-1.5"
                        style={{ backgroundColor: selectedCondition === c ? '#2563EB' : '#F3F4F6' }}
                    >
                        <Text style={{ color: selectedCondition === c ? '#fff' : '#6B7280', fontSize: 12 }}>{c.replace('_', ' ')}</Text>
                    </TouchableOpacity>
                ))}
            </ScrollView>

            {/* Price range */}
            <View className="flex-row px-4 gap-2 mb-3">
                <TextInput
                    className="flex-1 bg-card border border-gray-200 rounded-xl px-3 py-2 text-sm text-text-primary"
                    placeholder="Min price"
                    value={minPrice}
                    onChangeText={setMinPrice}
                    keyboardType="numeric"
                />
                <TextInput
                    className="flex-1 bg-card border border-gray-200 rounded-xl px-3 py-2 text-sm text-text-primary"
                    placeholder="Max price"
                    value={maxPrice}
                    onChangeText={setMaxPrice}
                    keyboardType="numeric"
                />
            </View>

            {/* Results */}
            {isLoading ? (
                <View className="flex-1 justify-center items-center">
                    <ActivityIndicator size="large" color="#1A6B3C" />
                </View>
            ) : products.length === 0 ? (
                <EmptyState icon="🔍" title="No results" message={debouncedQ ? `Nothing found for "${debouncedQ}"` : 'Try searching for something'} />
            ) : (
                <FlatList
                    data={products}
                    keyExtractor={(item) => item.id}
                    numColumns={2}
                    contentContainerStyle={{ paddingHorizontal: 8, paddingBottom: 20 }}
                    renderItem={({ item }) => <ProductCard product={item} />}
                    showsVerticalScrollIndicator={false}
                />
            )}
        </SafeAreaView>
    );
}

export default function SearchScreen() {
    return <ErrorBoundary><SearchContent /></ErrorBoundary>;
}
